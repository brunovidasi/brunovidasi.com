<?php

class DiscogsException extends RuntimeException
{
}

/**
 * The Discogs API, as much of it as this app needs.
 *
 * Reading (collection, wantlist, release detail) is all this does today.
 * Organising the collection from here — moving a copy between folders, editing
 * its notes or rating, changing a wantlist line — is the same API with a
 * different verb, so request() takes a method and a body and the write helpers
 * at the bottom are one line each when that day comes. The personal access
 * token already authorises them; nothing else has to change.
 *
 * Rate limits: 60 requests a minute with a token, 25 without, counted in a
 * rolling window. Going over earns a 429 and, repeated, a temporary block — so
 * this throttles itself to the configured ceiling BEFORE sending, and also
 * listens to what the response headers say is left.
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
        // Never assume the authenticated ceiling without a token.
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

    /** Who the token belongs to — used by the admin to prove it works. */
    public function identity(): array
    {
        return $this->request('GET', '/oauth/identity');
    }

    /**
     * One page of the collection. Folder 0 is Discogs' "All" folder, which is
     * what the site shows: every copy, in every folder, once.
     */
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

    /* ---------- Writing back to Discogs (see requirement 6; not wired up yet) ----------
     *
     * These are the calls the "organise my collection from here" work will use.
     * They are deliberately left unimplemented rather than half-implemented: a
     * wrong POST against a real collection is not an error you can undo from
     * here, and none of the pages call them yet.
     *
     *   POST   /users/{u}/collection/folders/{folder}/releases/{id}   add a copy
     *   DELETE /users/{u}/collection/folders/{f}/releases/{id}/instances/{i}
     *   POST   /users/{u}/collection/folders/{f}/releases/{id}/instances/{i}   move / rate
     *   PUT    /users/{u}/wants/{id}                                  add to wantlist
     *
     * request() already speaks every verb, so each is one method when needed.
     */

    /**
     * @throws DiscogsException on transport failure, a non-2xx status, or a body
     *                          that isn't JSON.
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
                // A dropped connection mid-sync is common enough on shared
                // hosting to be worth one retry before giving up on the run.
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
                // Over the window. Wait out a full one rather than creeping back
                // in: repeated 429s are what turns into a temporary ban.
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

    /**
     * Spaces requests out so the configured ceiling can't be crossed, and slows
     * right down when the response headers say the window is nearly spent.
     */
    private function throttle(): void
    {
        $gap = 60.0 / $this->rateLimit;

        // Under five calls left in the window: crawl, don't sprint.
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
