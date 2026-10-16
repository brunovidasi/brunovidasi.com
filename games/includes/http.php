<?php

/**
 * One cURL client for every outside call: the platforms' APIs and the cover
 * sources. It counts the calls (for Sync History), waits and tries again when a
 * service says to slow down, and keeps the cookies a response sets for the
 * callers that need them (Rockstar).
 */

final class HttpResponse
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        /** lower-cased name => last value */
        public readonly array $headers,
        /** name => value, from Set-Cookie */
        public readonly array $cookies,
    ) {
    }

    public function json(): mixed
    {
        return json_decode($this->body, true);
    }

    public function ok(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /** The start of the body, for an error message. */
    public function excerpt(int $length = 200): string
    {
        return mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($this->body))), 0, $length);
    }
}

final class Http
{
    public static int $calls = 0;

    /**
     * @param array $options timeout, user_agent, retries (on 429 and 5xx), http1 (force HTTP/1.1), encoding
     */
    public static function request(string $method, string $url, array $headers = [], ?string $body = null, array $options = []): HttpResponse
    {
        $retries = $options['retries'] ?? 3;

        for ($attempt = 0; ; $attempt++) {
            $response = self::once($method, $url, $headers, $body, $options);
            $retryable = $response->status === 429 || $response->status >= 500;
            if (!$retryable || $attempt >= $retries) {
                return $response;
            }
            $wait = (int) ($response->headers['retry-after'] ?? 0);
            sleep(max(2 ** ($attempt + 1), min($wait, 30)));
        }
    }

    public static function get(string $url, array $headers = [], array $options = []): HttpResponse
    {
        return self::request('GET', $url, $headers, null, $options);
    }

    /** GET a JSON document, or throw with what came back. */
    public static function getJson(string $url, array $headers = [], array $options = []): array
    {
        $r = self::get($url, ['Accept: application/json', ...$headers], $options);
        $data = $r->json();
        if (!$r->ok() || !is_array($data)) {
            throw new RuntimeException("HTTP {$r->status} from " . parse_url($url, PHP_URL_HOST) . ': ' . $r->excerpt());
        }

        return $data;
    }

    private static function once(string $method, string $url, array $headers, ?string $body, array $options): HttpResponse
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP\'s cURL extension is needed to talk to the platforms.');
        }

        self::$calls++;
        $respHeaders = [];
        $cookies = [];
        $ch = curl_init($url);
        $opts = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_TIMEOUT        => $options['timeout'] ?? 45,
            CURLOPT_FOLLOWLOCATION => $options['follow'] ?? true,
            CURLOPT_USERAGENT      => $options['user_agent'] ?? "BrunosGameShelf/1.0 (+https://brunovida.si/games)",
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_ENCODING       => $options['encoding'] ?? '',
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$respHeaders, &$cookies) {
                if (preg_match('/^set-cookie:\s*([^=;\s]+)=([^;]*)/i', $line, $m)) {
                    $cookies[$m[1]] = $m[2];
                } elseif (str_contains($line, ':')) {
                    [$k, $v] = explode(':', $line, 2);
                    $respHeaders[strtolower(trim($k))] = trim($v);
                }
                return strlen($line);
            },
        ];
        if (!empty($options['http1'])) {
            $opts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
        }
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);

        $res = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($res === false) {
            throw new RuntimeException('Could not reach ' . parse_url($url, PHP_URL_HOST) . ": $err");
        }

        return new HttpResponse($status, (string) $res, $respHeaders, $cookies);
    }
}

function b64url(string $bytes): string
{
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

/** The claims of a JWT, unchecked: only to read when it runs out and whose it is. */
function jwt_claims(string $jwt): array
{
    $part = explode('.', $jwt)[1] ?? '';

    return json_decode((string) base64_decode(strtr($part, '-_', '+/')), true) ?: [];
}
