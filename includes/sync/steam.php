<?php

/**
 * Steam, through its official Web API (a key and the SteamID64, Settings →
 * Connections): the library with its play time, each game's achievements, and
 * the store page of each game. Was api/steam-exporter + tools/steam-sync.mjs.
 *
 * A game in the library with no copy tied to it gets a new digital one
 * ("launcher": "steam"), with the game's library art as its cover. A digital PC
 * copy with the same title, no steamAppId and no other launcher is tied to it
 * instead; a boxed copy never is, as a Steam copy is a copy of its own (a disc
 * that activates on Steam is tied by hand, with "alsoDigital").
 */
final class SteamSync extends PlatformSync
{
    private const API = 'https://api.steampowered.com';
    private const CDN = 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps';
    // the store's prices say which shop the account buys in, and so a new copy's region
    private const REGION_BY_CURRENCY = ['AUD' => 'AUS', 'NZD' => 'AUS', 'GBP' => 'UK', 'EUR' => 'EUR', 'USD' => 'USA', 'JPY' => 'JPN'];
    // Steam's genres that say how it is sold rather than what it is
    private const NOT_A_GENRE = ['Free To Play', 'Free to Play', 'Indie', 'Early Access', 'Massively Multiplayer'];
    // a store page is asked for again after this long; the store allows about 200 a five minutes
    private const STORE_TTL = 14 * 86400;
    private const STORE_PER_RUN = 60;

    public function connected(): bool
    {
        return credential('steam', 'api_key') !== null && credential('steam', 'steam_id') !== null;
    }

    /**
     * "Sign in through Steam" (Steam's OpenID 2.0), which only says whose
     * profile it is: it fills in the SteamID64. Steam sends you back to
     * $returnTo with the answer, which verifySignIn() checks with Steam itself.
     */
    public static function signInUrl(string $returnTo): string
    {
        return 'https://steamcommunity.com/openid/login?' . http_build_query([
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $returnTo,
            'openid.realm' => base_url() . '/',
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ]);
    }

    /** The SteamID64 from Steam's answer, once Steam has said the answer is its own. */
    public static function verifySignIn(array $query): string
    {
        // PHP turns the dots in openid.* into underscores
        $params = [];
        foreach ($query as $k => $v) {
            if (str_starts_with($k, 'openid_') && is_string($v)) {
                $params['openid.' . substr($k, 7)] = $v;
            }
        }
        if (($params['openid.mode'] ?? '') !== 'id_res') {
            throw new RuntimeException('Steam didn\'t sign you in' . (($params['openid.mode'] ?? '') === 'cancel' ? ' (cancelled).' : '.'));
        }
        if (!str_starts_with($params['openid.return_to'] ?? '', base_url() . '/')) {
            throw new RuntimeException('Steam\'s answer was meant for another site.');
        }
        $params['openid.mode'] = 'check_authentication';
        $r = Http::request('POST', 'https://steamcommunity.com/openid/login', ['Content-Type: application/x-www-form-urlencoded'], http_build_query($params), ['retries' => 1]);
        if (!str_contains($r->body, 'is_valid:true')) {
            throw new RuntimeException('Steam didn\'t confirm the sign-in. Try again.');
        }
        if (!preg_match('#^https://steamcommunity\.com/openid/id/(\d{17})$#', $params['openid.claimed_id'] ?? '', $m)) {
            throw new RuntimeException('Steam\'s answer had no SteamID.');
        }

        return $m[1];
    }

    /** Settings → Connections: whether the key and the SteamID work. Returns the profile's name. */
    public static function check(): ?string
    {
        $self = new self('steam');
        $profile = $self->api('ISteamUser/GetPlayerSummaries', ['steamids' => credential('steam', 'steam_id')])['response']['players'][0] ?? null;
        if (!$profile) {
            throw new RuntimeException('The key works, but Steam knows no profile with that SteamID64.');
        }

        return $profile['personaname'] ?? null;
    }

    private function api(string $path, array $params): array
    {
        $params['key'] = credential('steam', 'api_key');
        $params['format'] = 'json';
        $r = Http::get(self::API . "/$path/v1/?" . http_build_query($params), ['Accept: application/json']);
        if ($r->status === 401 || $r->status === 403) {
            throw new AuthError('Steam refused the API key (HTTP ' . $r->status . '). Check it in Settings → Connections.');
        }
        $data = $r->json();
        if (!$r->ok() || !is_array($data)) {
            throw new RuntimeException("Steam $path: HTTP {$r->status} " . $r->excerpt());
        }

        return $data;
    }

    public function fetch(SyncRun $run): array
    {
        $sid = (string) credential('steam', 'steam_id');
        $out = ['exported_at_utc' => gmdate('c'), 'data' => []];

        $profile = $this->api('ISteamUser/GetPlayerSummaries', ['steamids' => $sid])['response']['players'][0] ?? null;
        if (!$profile) {
            throw new AuthError('Steam knows no profile with that SteamID64.');
        }
        $run->account = $profile['personaname'] ?? null;

        $games = $this->api('IPlayerService/GetOwnedGames', [
            'steamid' => $sid, 'include_appinfo' => 1, 'include_played_free_games' => 0, 'include_free_sub' => 1, 'include_extended_appinfo' => 1,
        ])['response']['games'] ?? null;
        if ($games === null) {
            throw new RuntimeException('Steam returned no library: is the profile\'s "Game details" set to public?');
        }
        $out['data']['owned_games'] = $games;
        $out['data']['recently_played'] = $this->api('IPlayerService/GetRecentlyPlayedGames', ['steamid' => $sid, 'count' => 0])['response']['games'] ?? [];
        $run->log(count($games) . ' games in the library.');

        // the store pages and the achievement names change rarely: asked again after a while
        $previous = platform_records('steam', 'games');
        $syncedAt = $this->syncedAt();
        $due = [];
        foreach ($games as $g) {
            $id = (string) $g['appid'];
            $age = time() - ($syncedAt[$id] ?? 0);
            if (!isset($previous[$id]) || empty($previous[$id]['store']) || $age > self::STORE_TTL) {
                $due[$id] = $age;
            }
        }
        arsort($due);
        $due = array_slice(array_keys($due), 0, self::STORE_PER_RUN);

        $achievements = $schemas = $details = [];
        foreach ($games as $g) {
            $app = (int) $g['appid'];
            try {
                $a = $this->api('ISteamUserStats/GetPlayerAchievements', ['steamid' => $sid, 'appid' => $app, 'l' => 'english']);
                $achievements[(string) $app] = ['appid' => $app, 'playerstats' => $a['playerstats'] ?? null];
            } catch (RuntimeException) {
                // a game without achievements answers 400
            }
            if (in_array((string) $app, $due, true)) {
                try {
                    $s = $this->api('ISteamUserStats/GetSchemaForGame', ['appid' => $app, 'l' => 'english']);
                    $schemas[(string) $app] = ['appid' => $app, 'schema' => $s['game'] ?? $s];
                } catch (RuntimeException) {
                }
                $d = Http::get('https://store.steampowered.com/api/appdetails?' . http_build_query(['appids' => $app, 'l' => 'english', 'cc' => 'au']))->json();
                if (!empty($d[(string) $app]['success'])) {
                    $details[(string) $app] = $d[(string) $app]['data'];
                }
                usleep(200000);
            }
        }
        $out['data']['achievements_by_appid'] = $achievements;
        $out['data']['game_schemas_by_appid'] = $schemas;
        $out['data']['store_details_by_appid'] = $details;
        $run->log(count($details) . ' store ' . plural(count($details), 'page') . ' fetched; the rest kept from before.');

        return $out;
    }

    /** ext_id => when its record was last written, as a Unix time. */
    private function syncedAt(): array
    {
        $stmt = db()->prepare("SELECT ext_id, synced_at FROM platform_records WHERE platform = 'steam' AND collection = 'games'");
        $stmt->execute();

        return array_map(fn ($t) => (int) to_timestamp($t), array_column($stmt->fetchAll(), 'synced_at', 'ext_id'));
    }

    /** As tools/steam-sync.mjs's fromExport(). A store page or achievement list not asked for this time is kept from before. */
    public function normalize(array $raw, SyncRun $run): array
    {
        $d = $raw['data'] ?? [];
        $owned = $d['owned_games'] ?? [];
        $store = $d['store_details_by_appid'] ?? [];
        $ach = $d['achievements_by_appid'] ?? [];
        $schemas = $d['game_schemas_by_appid'] ?? [];
        $recent = array_column($d['recently_played'] ?? [], 'playtime_2weeks', 'appid');
        $previous = platform_records('steam', 'games');

        $currency = null;
        foreach ($store as $s) {
            if (!empty($s['price_overview']['currency'])) {
                $currency = $s['price_overview']['currency'];
                break;
            }
        }
        $currency ??= platform_account('steam')['currency'] ?? null;

        $games = [];
        foreach ($owned as $o) {
            $id = (string) $o['appid'];
            $prev = $previous[$id] ?? null;
            $s = $store[$id] ?? null;
            $stats = $ach[$id]['playerstats'] ?? null;
            $got = ($stats['success'] ?? true) !== false ? array_values((array) ($stats['achievements'] ?? [])) : [];
            $unlocked = [];
            foreach ($got as $a) {
                if (!empty($a['achieved'])) {
                    $unlocked[$a['apiname']] = $a['unlocktime'] ?? null;
                }
            }

            // the achievements' names: the schema when asked for, else the list kept from before
            // (a list, or an object by each achievement's name: then the name is its key)
            $defs = null;
            if (isset($schemas[$id])) {
                $defs = [];
                foreach ((array) ($schemas[$id]['schema']['availableGameStats']['achievements'] ?? []) as $key => $a) {
                    $defs[] = $a + ['name' => is_string($key) ? $key : null];
                }
            }
            if ($defs === null) {
                $defs = array_map(fn ($a) => ['name' => $a['id'] ?? $a['name'] ?? null, 'displayName' => $a['name'], 'description' => $a['description'], 'icon' => $a['icon'], 'hidden' => $a['hidden'] ? 1 : 0], $prev['achievements']['list'] ?? []);
            }

            $storeRecord = $s ? $this->storeRecord($s) : ($prev['store'] ?? null);
            $total = $s ? ($s['achievements']['total'] ?? (count($defs) ?: null)) : ($prev['achievements']['total'] ?? (count($defs) ?: null));

            $games[$id] = [
                'appid' => (int) $o['appid'],
                'name' => clean_name($s['name'] ?? $storeRecord['name'] ?? $o['name'] ?? ''),
                'storeUrl' => 'https://store.steampowered.com/app/' . $o['appid'],
                'playtime' => [
                    'total' => $o['playtime_forever'] ?? 0,
                    'windows' => $o['playtime_windows_forever'] ?? 0,
                    'mac' => $o['playtime_mac_forever'] ?? 0,
                    'linux' => $o['playtime_linux_forever'] ?? 0,
                    'deck' => $o['playtime_deck_forever'] ?? 0,
                    'lastTwoWeeks' => ($o['playtime_2weeks'] ?? 0) ?: ($recent[$o['appid']] ?? 0),
                ],
                'lastPlayed' => iso_date($o['rtime_last_played'] ?? null),
                'achievements' => [
                    'total' => $total,
                    'unlocked' => $got ? count($unlocked) : null,
                    'list' => array_map(fn ($a) => [
                        'id' => $a['name'],
                        'name' => $a['displayName'] ?? $a['name'],
                        'description' => ($a['description'] ?? '') ?: null,
                        'icon' => ($a['icon'] ?? '') ?: null,
                        'hidden' => !empty($a['hidden']),
                        'unlocked' => isset($a['name']) && array_key_exists($a['name'], $unlocked) ? (iso_date($unlocked[$a['name']]) ?? true) : ($got ? false : null),
                    ], $defs),
                ],
                'images' => [
                    'library' => self::CDN . '/' . $o['appid'] . '/' . ($o['capsule_filename'] ?? 'library_600x900.jpg'),
                    'icon' => !empty($o['img_icon_url']) ? "https://media.steampowered.com/steamcommunity/public/images/apps/{$o['appid']}/{$o['img_icon_url']}.jpg" : null,
                    'header' => $s['header_image'] ?? $prev['images']['header'] ?? null,
                    'capsule' => $s ? ($s['capsule_imagev5'] ?? $s['capsule_image'] ?? null) : ($prev['images']['capsule'] ?? null),
                    'background' => $s ? ($s['background_raw'] ?? $s['background'] ?? null) : ($prev['images']['background'] ?? null),
                ],
                'store' => $storeRecord,
                'flags' => ['workshop' => !empty($o['has_workshop']), 'market' => !empty($o['has_market']), 'dlc' => !empty($o['has_dlc'])],
            ];
            // the name rides in the store record only to be found again above
            unset($games[$id]['store']['name']);
        }

        return [
            'account' => [
                'synced' => $raw['exported_at_utc'] ?? gmdate('c'),
                'source' => 'steam',
                'currency' => $currency,
                'totals' => ['games' => count($owned), 'playtime' => array_sum(array_map(fn ($o) => $o['playtime_forever'] ?? 0, $owned))],
            ],
            'collections' => ['games' => $games],
        ];
    }

    private function storeRecord(array $s): array
    {
        $text = function (?string $html): string {
            $t = preg_replace(['/<br\s*\/?>/i', '/<\/(p|li|h\d)>/i'], "\n", (string) $html);
            return clean_name(html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5));
        };
        $languages = array_values(array_filter(array_map(
            fn ($l) => trim(explode("\n", $l)[0]),
            preg_split('/\s*,\s*/', str_replace('*', '', $text($s['supported_languages'] ?? '')))
        )));
        $ratings = [];
        foreach ($s['ratings'] ?? [] as $k => $r) {
            if (isset($r['rating']) && $r['rating'] !== '') {
                $ratings[$k] = $r['rating'];
            }
        }
        $price = $s['price_overview'] ?? null;

        return [
            'name' => $s['name'] ?? null,
            'type' => $s['type'] ?? null,
            'free' => !empty($s['is_free']),
            'description' => clean_name($s['short_description'] ?? ''),
            'about' => $text($s['about_the_game'] ?? ''),
            'released' => self::isoDay($s['release_date']['date'] ?? null),
            'comingSoon' => !empty($s['release_date']['coming_soon']),
            'developers' => array_map('clean_name', $s['developers'] ?? []),
            'publishers' => array_map('clean_name', $s['publishers'] ?? []),
            'genres' => array_column($s['genres'] ?? [], 'description'),
            'categories' => array_column($s['categories'] ?? [], 'description'),
            'platforms' => $s['platforms'] ?? null,
            'controllerSupport' => $s['controller_support'] ?? null,
            'languages' => $languages,
            'metacritic' => $s['metacritic'] ?? null,
            'recommendations' => $s['recommendations']['total'] ?? null,
            'ageRatings' => $ratings,
            'price' => $price ? ['currency' => $price['currency'], 'initial' => $price['initial'] / 100, 'final' => $price['final'] / 100, 'discount' => $price['discount_percent']] : null,
            'dlc' => $s['dlc'] ?? [],
            'website' => $s['website'] ?? null,
            'screenshots' => array_column($s['screenshots'] ?? [], 'path_full'),
            'trailers' => array_map(fn ($m) => ['name' => $m['name'] ?? null, 'thumbnail' => $m['thumbnail'] ?? null, 'video' => $m['hls_h264'] ?? $m['dash_h264'] ?? null], $s['movies'] ?? []),
        ];
    }

    /** "6 Feb, 2013" or "Feb 6, 2013" → "2013-02-06"; "Feb 2013" → "2013-02"; "2013" → "2013" */
    private static function isoDay(?string $s): ?string
    {
        if (!$s) {
            return null;
        }
        $months = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];
        $m3 = fn ($w) => $months[strtolower(substr($w, 0, 3))] ?? null;
        if (preg_match('/^(\d{1,2}) (\w{3})\w*,? (\d{4})$/', $s, $m) && $m3($m[2])) {
            return sprintf('%s-%02d-%02d', $m[3], $m3($m[2]), $m[1]);
        }
        if (preg_match('/^(\w{3})\w* (\d{1,2}),? (\d{4})$/', $s, $m) && $m3($m[1])) {
            return sprintf('%s-%02d-%02d', $m[3], $m3($m[1]), $m[2]);
        }
        if (preg_match('/^(\w{3})\w* (\d{4})$/', $s, $m) && $m3($m[1])) {
            return sprintf('%s-%02d', $m[2], $m3($m[1]));
        }

        return preg_match('/^(\d{4})$/', $s, $m) ? $m[1] : null;
    }

    public function merge(array $data, SyncRun $run): void
    {
        $steam = $data['collections']['games'];
        $region = self::REGION_BY_CURRENCY[$data['account']['currency'] ?? ''] ?? 'PAL';

        $byApp = [];
        foreach ($run->games as $id => $g) {
            if (!empty($g['steamAppId'])) {
                $byApp[(string) $g['steamAppId']] = $id;
            }
        }

        foreach ($steam as $appId => $s) {
            $appId = (string) $appId;
            $id = $byApp[$appId] ?? null;
            if ($id === null) {
                // a digital PC copy already on the shelf under the same name, not in another launcher, is this game
                foreach ($run->games as $gid => $x) {
                    if ($x['console'] === 'pc' && ($x['format'] ?? '') === 'digital' && empty($x['steamAppId'])
                        && in_array($x['launcher'] ?? null, [null, '', 'steam'], true)
                        && in_array(norm_name($s['name']), [norm_name($x['title']), norm_name($x['title'] . ' ' . ($x['edition'] ?? ''))], true)) {
                        $id = $gid;
                        break;
                    }
                }
                if ($id !== null) {
                    $run->link($id, 'steamAppId', (int) $appId, $s['name']);
                    $run->fill($id, 'launcher', 'steam');
                } else {
                    $id = $run->add($this->newCopy($s, $region), '-steam', $s['name']);
                }
                $byApp[$appId] = $id;
            }

            // the fields the sync may fill in when they are empty (or still its own)
            $st = $s['store'] ?? [];
            if ($run->fill($id, 'released', $st['released'] ?? null)) {
                $run->fill($id, 'dateSource', 'steam');
            }
            $run->fill($id, 'publisher', ($st['publishers'] ?? []) ? implode(', ', $st['publishers']) : null);
            $run->fill($id, 'developer', ($st['developers'] ?? []) ? implode(', ', $st['developers']) : null);
            $run->fill($id, 'genre', $this->genre($st));

            if (empty($run->games[$id]['cover'])) {
                try {
                    $cover = $this->libraryArt($run->games[$id], $s);
                    $run->fill($id, 'cover', $cover['cover']);
                    $run->fill($id, 'colors', $cover['colors']);
                } catch (Throwable $e) {
                    $run->log("No cover for {$s['name']}: " . $e->getMessage());
                }
            }
        }

        // a copy tied to Steam that the library no longer lists stays; it is only noted
        foreach ($run->games as $id => $g) {
            if (!empty($g['steamAppId']) && !isset($steam[(string) $g['steamAppId']])) {
                $run->change('missing', $id, 'steamAppId', null, null, $g['title'], 'Not in the Steam library any more; the copy stays.');
            }
        }
    }

    private function genre(array $store): ?string
    {
        foreach ($store['genres'] ?? [] as $g) {
            if (!in_array($g, self::NOT_A_GENRE, true)) {
                return $g;
            }
        }

        return $store['genres'][0] ?? null;
    }

    private function newCopy(array $s, string $region): array
    {
        $st = $s['store'] ?? [];

        return array_filter([
            'id' => 'pc-' . slugify($s['name']),
            'console' => 'pc',
            'title' => $s['name'],
            'region' => $region,
            'released' => $st['released'] ?? null,
            'dateSource' => 'steam',
            'publisher' => ($st['publishers'] ?? []) ? implode(', ', $st['publishers']) : null,
            'developer' => ($st['developers'] ?? []) ? implode(', ', $st['developers']) : null,
            'genre' => $this->genre($st),
            'format' => 'digital',
            'launcher' => 'steam',
            'mac' => !empty($st['platforms']['mac']) ? true : null,
            'steamAppId' => $s['appid'],
            'cover' => null,
            'colors' => null,
        ], fn ($v) => $v !== null);
    }

    /** The game's library art (600×900) as the copy's cover. */
    private function libraryArt(array $game, array $s): array
    {
        $r = Http::get($s['images']['library']);
        if (!$r->ok()) {
            throw new RuntimeException("HTTP {$r->status}");
        }
        $saved = save_cover_image($r->body, cover_path_for($game));

        return [
            'cover' => ['file' => $saved['file'], 'ratio' => $saved['ratio'], 'source' => 'steam', 'ref' => "{$s['name']} · Steam library art · app {$s['appid']}"],
            'colors' => $saved['colors'],
        ];
    }
}
