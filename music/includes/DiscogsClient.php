<?php

class DiscogsException extends RuntimeException
{
}

/**
 * The Discogs API, as much of it as this app reads. It throttles itself below
 * the rate limit (60 a minute with a token, 25 without) before sending, and
 * slows down further when the response headers say the window is nearly spent.
 *
 * Writing back is not built on purpose: a wrong POST against a real collection
 * can't be undone from here. request() already takes any verb, and these are
 * the endpoints it would need:
 *
 *   POST   /users/{u}/collection/folders/{folder}/releases/{id}                 add a copy
 *   DELETE /users/{u}/collection/folders/{f}/releases/{id}/instances/{i}        remove a copy
 *   POST   /users/{u}/collection/folders/{f}/releases/{id}/instances/{i}        move or rate
 *   PUT    /users/{u}/wants/{id}                                                add to wantlist
 */
class DiscogsClient
{
    private string $token;
    private string $userAgent;
    private int $rateLimit;

    private int $calls = 0;
    private float $lastRequestAt = 0.0;
    private ?int $remaining = null;

    public function __construct(?array $config = null)
    {
        $config ??= discogs_config();
        $this->token = (string) ($config['token'] ?? '');
        $this->userAgent = (string) ($config['user_agent'] ?? 'BrunoVinylCollection/1.0');
        $ceiling = $this->token !== '' ? 60 : 25;
        $this->rateLimit = max(1, min((int) ($config['rate_limit'] ?? $ceiling), $ceiling));
    }

    public function hasToken(): bool
    {
        return $this->token !== '';
    }

    public function callsMade(): int
    {
        return $this->calls;
    }

    /** Who the token belongs to, to prove it works. */
    public function identity(): array
    {
        return $this->request('GET', '/oauth/identity');
    }

    /** One page of the collection. Folder 0 is Discogs' "All": every copy, once. */
    public function collectionPage(string $username, int $page, int $perPage = 100, int $folder = 0): array
    {
        return $this->request('GET', sprintf(
            '/users/%s/collection/folders/%d/releases?page=%d&per_page=%d&sort=added&sort_order=desc',
            rawurlencode($username),
            $folder,
            $page,
            $perPage
        ));
    }

    public function wantlistPage(string $username, int $page, int $perPage = 100): array
    {
        return $this->request('GET', sprintf(
            '/users/%s/wants?page=%d&per_page=%d',
            rawurlencode($username),
            $page,
            $perPage
        ));
    }

    /** Everything about one release: tracklist, credits, images, identifiers. */
    public function release(int $id): array
    {
        return $this->request('GET', '/releases/' . $id);
    }

    public function search(string $query, array $params = []): array
    {
        $params = ['q' => $query, 'per_page' => 25] + $params;
        return $this->request('GET', '/database/search?' . http_build_query($params));
    }

    /**
     * @throws DiscogsException on a failed connection, a non-2xx status, or a body that isn't JSON
     */
    public function request(string $method, string $path, ?array $body = null): array
    {
        $url = 'https://api.discogs.com' . $path;

        $attempt = 0;
        while (true) {
            $attempt++;
            $this->throttle();

            $headers = [
                'User-Agent: ' . $this->userAgent,
                'Accept: application/vnd.discogs.v2.discogs+json',
            ];
            if ($this->token !== '') {
                $headers[] = 'Authorization: Discogs token=' . $this->token;
            }

            $curl = curl_init();
            $options = [
                CURLOPT_URL            => $url,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => false,
            ];
            if ($body !== null) {
                $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
                $headers[] = 'Content-Type: application/json';
            }
            $options[CURLOPT_HTTPHEADER] = $headers;
            curl_setopt_array($curl, $options);

            $response = curl_exec($curl);
            $this->calls++;
            $this->lastRequestAt = microtime(true);

            if ($response === false) {
                $error = curl_error($curl);
                if ($attempt < 3) {
                    sleep(2 * $attempt);
                    continue;
                }
                throw new DiscogsException("Couldn't reach Discogs: $error");
            }

            $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

            $rawHeaders = substr($response, 0, $headerSize);
            $rawBody = substr($response, $headerSize);
            $this->readRateLimit($rawHeaders);

            if ($status === 429) {
                // Wait out a whole window: repeated 429s turn into a temporary ban.
                if ($attempt < 3) {
                    sleep(60);
                    continue;
                }
                throw new DiscogsException('Discogs is rate-limiting this token. Wait a minute and sync again.');
            }

            if ($status === 401) {
                throw new DiscogsException('Discogs rejected the token (401). Check the token in the config.');
            }
            if ($status === 404) {
                throw new DiscogsException("Discogs has nothing at $path (404).");
            }
            if ($status >= 500 && $attempt < 3) {
                sleep(3 * $attempt);
                continue;
            }
            if ($status < 200 || $status >= 300) {
                $message = json_decode($rawBody, true)['message'] ?? trim(substr($rawBody, 0, 200));
                throw new DiscogsException("Discogs responded with $status: $message");
            }

            $decoded = json_decode($rawBody, true);
            if (!is_array($decoded)) {
                throw new DiscogsException('Discogs sent something that is not JSON.');
            }

            return $decoded;
        }
    }

    private function throttle(): void
    {
        $gap = 60.0 / $this->rateLimit;

        if ($this->remaining !== null && $this->remaining < 5) {
            $gap = max($gap, 3.0);
        }

        $wait = $gap - (microtime(true) - $this->lastRequestAt);
        if ($this->lastRequestAt > 0 && $wait > 0) {
            usleep((int) ($wait * 1_000_000));
        }
    }

    private function readRateLimit(string $rawHeaders): void
    {
        if (preg_match('/^x-discogs-ratelimit-remaining:\s*(\d+)/mi', $rawHeaders, $m)) {
            $this->remaining = (int) $m[1];
        }
    }
}
