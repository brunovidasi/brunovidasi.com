<?php

/**
 * The EA app, through the GraphQL service it uses (undocumented; EA can change
 * it): every game and add-on the EA account owns on PC, the play time and last
 * session the EA app keeps for each game, and its achievements. Was
 * api/ea-exporter + tools/ea-sync.mjs.
 *
 * It signs in as the EA app does: Settings → Connections opens EA's sign-in,
 * and the address it ends on (the "location" of its last auth request, as the
 * page explains) is pasted back. That gives a refresh token, which every sync
 * renews.
 *
 * A game or add-on is tied to the one PC copy with the same name (™, a year in
 * brackets and "Expansion Pack" at the end don't count); of several, the one
 * that is digital or a disc also in the EA app. A game with no copy at all was
 * bought in the EA app, so it gets a new digital one; an add-on takes its game's
 * name before its own ("The Sims 4: Grunge Revival Kit"), its series, publisher,
 * developer and genre, and a pack from its name.
 */
final class EaSync extends PlatformSync
{
    // the EA app's own client (the same one the GOG Galaxy EA plugin uses)
    private const CLIENT_ID = 'JUNO_PC_CLIENT';
    private const CLIENT_SECRET = '4mRLtYMb6vq9qglomWEaT4ChxsXWcyqbQpuBNfMPOYOiDmYYQmjuaBsF2Zp0RyVeWkfqhE9TuGgAw7te';
    private const REDIRECT_URI = 'qrc:///html/login_successful.html';
    private const ACCOUNTS = 'https://accounts.ea.com/connect';
    private const GRAPHQL = 'https://service-aggregation-layer.juno.ea.com/graphql';
    // the EA app signs each sign-in with a hash of the PC; EA accepts it with placeholder hardware values
    private const PC_SIGN_KEY = 'ISa3dpGOc8wW7Adn4auACSQmaccrOyR2';
    private const BATCH = 50;
    private const REGION_BY_STORE = ['au' => 'AUS', 'nz' => 'AUS', 'gb' => 'UK', 'uk' => 'UK', 'us' => 'USA', 'jp' => 'JPN'];
    // the parts a game is shipped in, and Battlefield's free mode: not a pack of their own
    private const NOT_A_PACK = '/HD Textures|Shared Data|\bMultiplayer\b|\bSingle Player\b|REDSEC/i';

    private const OWNED_QUERY = 'query { me { ownedGameProducts(
        storefronts: [EA], locale: "DEFAULT", paging: {limit: 9999, next: null}, productFound: true,
        orderBy: {field: NAME, direction: ASC}, ownershipMethod: [PURCHASE, REDEMPTION, ENTITLEMENT_GRANT],
        downloadableOnly: false, entitlementEnabled: true, platforms: [PC]
    ) { items {
        originOfferId status
        product { id name downloadable gameSlug trialDetails { trialType }
            baseItem(availabilities: [VISIBLE]) { id title baseGameSlug gameType }
            gameProductUser(storefronts: [EA]) { ownershipMethods initialEntitlementDate entitlementId status } }
    } } } }';

    // add-ons (expansions, packs, kits) only come back when asked for by type; the EA app's own library asks so
    private const ADDONS_QUERY = 'query { me { ownedGameProducts(
        locale: "DEFAULT", entitlementEnabled: true, storefronts: [EA], platforms: [PC], paging: {limit: 9999},
        type: [DIGITAL_EXTRA_CONTENT, PACKAGED_EXTRA_CONTENT]
    ) { items {
        originOfferId
        product { id name gameSlug baseItem { title gameType }
            gameProductUser { ownershipMethods initialEntitlementDate } }
    } } } }';

    private ?string $access = null;

    public function connected(): bool
    {
        return credential('ea', 'refresh_token') !== null;
    }

    private static function pcSign(): string
    {
        $payload = b64url(json_encode([
            'av' => 'v1', 'bsn' => 'None', 'gid' => 0, 'hsn' => 'None', 'mid' => (string) random_int(10 ** 15, PHP_INT_MAX),
            'msn' => 'None', 'sv' => 'v1', 'ts' => gmdate('Y-m-d H:i:s') . ':000',
        ], JSON_UNESCAPED_SLASHES));

        return $payload . '.' . b64url(hash_hmac('sha256', $payload, self::PC_SIGN_KEY, true));
    }

    /** Settings → Connections, step 1: EA's sign-in page. $verifier is kept in the admin's session. */
    public static function signInUrl(string $verifier): string
    {
        return self::ACCOUNTS . '/auth?' . http_build_query([
            'response_type' => 'code', 'client_id' => self::CLIENT_ID, 'display' => 'junoClient/login', 'redirect_uri' => self::REDIRECT_URI,
            'locale' => 'en_US', 'pc_sign' => self::pcSign(), 'code_challenge' => b64url(hash('sha256', $verifier, true)), 'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Step 2: the address the sign-in ended on (qrc:///html/login_successful.html?code=…), or the code alone. */
    public static function connect(string $link, string $verifier): ?string
    {
        $link = trim($link);
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
        $code = $query['code'] ?? (preg_match('/^[\w.-]{20,}$/', $link) ? $link : null);
        if (!$code) {
            throw new RuntimeException('That isn\'t the address: it contains ?code= (it starts with ' . self::REDIRECT_URI . ').');
        }
        $data = self::token(['grant_type' => 'authorization_code', 'code' => $code, 'code_verifier' => $verifier, 'redirect_uri' => self::REDIRECT_URI, 'token_format' => 'JWS']);
        if (empty($data['refresh_token'])) {
            throw new RuntimeException('EA gave no refresh token. The code works once, for a few minutes: sign in again.');
        }
        set_credential('ea', 'refresh_token', $data['refresh_token']);

        try {
            return self::gqlWith($data['access_token'], 'query { me { player { displayName } } }')['me']['player']['displayName'] ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function token(array $params): array
    {
        $r = Http::request('POST', self::ACCOUNTS . '/token', ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query($params + ['client_id' => self::CLIENT_ID, 'client_secret' => self::CLIENT_SECRET]), ['retries' => 1]);
        $data = $r->json();
        if ($r->status !== 200 || !is_array($data) || empty($data['access_token'])) {
            throw new AuthError('EA sign-in failed (HTTP ' . $r->status . ' ' . $r->excerpt(150) . '). Connect the EA app again.');
        }

        return $data;
    }

    private function accessToken(): string
    {
        if ($this->access === null) {
            $data = self::token(['grant_type' => 'refresh_token', 'refresh_token' => credential('ea', 'refresh_token')]);
            // EA may hand out a new refresh token: kept at once, so the sign-in lasts
            if (!empty($data['refresh_token'])) {
                set_credential('ea', 'refresh_token', $data['refresh_token']);
            }
            $this->access = $data['access_token'];
        }

        return $this->access;
    }

    private static function gqlWith(string $token, string $query): array
    {
        $r = Http::get(self::GRAPHQL . '?' . http_build_query(['query' => $query], '', '&', PHP_QUERY_RFC3986), [
            'Authorization: Bearer ' . $token, 'Accept: application/json', 'x-client-id: EAX-JUNO-CLIENT', 'Referer: https://pc.ea.com/',
        ], ['http1' => true, 'user_agent' => 'EAApp/PC/13.680.0.6193', 'timeout' => 60]);
        if ($r->status === 401) {
            throw new AuthError('EA refused the sign-in (HTTP 401). Connect the EA app again.');
        }
        $res = $r->json();
        if ($r->status !== 200 || !is_array($res) || !isset($res['data'])) {
            throw new RuntimeException('EA request failed: HTTP ' . $r->status . ' ' . $r->excerpt());
        }

        return $res['data'];
    }

    private function gql(string $query): array
    {
        return self::gqlWith($this->accessToken(), $query);
    }

    public function fetch(SyncRun $run): array
    {
        $token = $this->accessToken();
        $profile = $this->gql('query { me { player { pd psd displayName } } }')['me']['player'] ?? [];
        $run->account = $profile['displayName'] ?? null;
        $persona = (string) ($profile['psd'] ?? jwt_claims($token)['nexus']['psid'] ?? '');

        $owned = $this->gql(self::OWNED_QUERY)['me']['ownedGameProducts']['items'] ?? [];

        // an add-on EA has no product page for comes back without one: its legacy offer still names it
        $addons = $this->gql(self::ADDONS_QUERY)['me']['ownedGameProducts']['items'] ?? [];
        $unnamed = array_column(array_filter($addons, fn ($a) => empty($a['product'])), 'originOfferId');
        $legacy = [];
        foreach (array_chunk($unnamed, self::BATCH) as $chunk) {
            $q = 'query { legacyOffers(offerIds: ' . json_encode($chunk) . ', locale: "DEFAULT") { offerId: id displayName displayType } }';
            foreach ($this->gql($q)['legacyOffers'] ?? [] as $o) {
                $legacy[$o['offerId']] = $o;
            }
        }
        foreach ($addons as &$a) {
            if (empty($a['product'])) {
                $a['legacyOffer'] = $legacy[$a['originOfferId']] ?? null;
            }
        }
        unset($a);
        $run->log(count($owned) . ' games and ' . count($addons) . ' add-on offers owned.');

        // play time is kept per game slug, so add-ons share their game's
        $slugs = array_values(array_unique(array_filter(array_map(fn ($o) => $o['product']['gameSlug'] ?? null, $owned))));
        $played = [];
        foreach (array_chunk($slugs, self::BATCH) as $chunk) {
            $q = 'query { me { recentGames(gameSlugs: ' . json_encode($chunk) . ') { items { gameSlug lastSessionEndDate totalPlayTimeSeconds } } } }';
            foreach ($this->gql($q)['me']['recentGames']['items'] ?? [] as $g) {
                $played[$g['gameSlug']] = $g;
            }
        }

        // an offer's achievement set comes from its legacy offer record
        $sets = [];
        $achievements = [];
        if ($persona !== '') {
            foreach (array_chunk(array_column($owned, 'originOfferId'), self::BATCH) as $chunk) {
                $q = 'query { legacyOffers(offerIds: ' . json_encode($chunk) . ', locale: "DEFAULT") { offerId: id achievementSetOverride } }';
                foreach ($this->gql($q)['legacyOffers'] ?? [] as $o) {
                    if (!empty($o['achievementSetOverride'])) {
                        $sets[$o['offerId']] = (string) $o['achievementSetOverride'];
                    }
                }
            }
            foreach (array_unique($sets) as $set) {
                $q = 'query { achievements(achievementSetIds: ' . json_encode([$set]) . ', playerPsd: ' . json_encode($persona) . ', showHidden: true) { id achievements { id name awardCount date } } }';
                try {
                    $achievements[$set] = $this->gql($q)['achievements'][0]['achievements'] ?? [];
                } catch (RuntimeException $e) {
                    if ($e instanceof AuthError) {
                        throw $e;
                    }
                    $run->log("Achievements for set $set: " . $e->getMessage());
                }
            }
        }

        return ['exported_at_utc' => gmdate('c'), 'owned' => $owned, 'addons' => $addons, 'playTime' => $played, 'achievements' => ['offerToSet' => $sets, 'sets' => $achievements]];
    }

    /** EA shouts a few names ("SPORE"): a name in capitals only is put in title case. */
    private static function named(?string $s): string
    {
        $n = clean_name($s);

        return preg_match('/[a-z]/', $n) || !preg_match('/[A-Z]{2}/', $n) ? $n : ucwords(strtolower($n));
    }

    /** The store a product is sold in is at the end of its id ("…_VideoGameProduct_au_pc"). */
    private static function regionOf(?string $id): string
    {
        return preg_match('/_([a-z]{2})_pc$/', (string) $id, $m) ? (self::REGION_BY_STORE[$m[1]] ?? 'PAL') : 'PAL';
    }

    public static function norm(?string $s): string
    {
        $s = preg_replace(['/\s*\(\d{4}\)/', '/\s+expansion pack$/i'], '', clean_name($s));

        return norm_name($s);
    }

    /** As tools/ea-sync.mjs's fromExport(). */
    public function normalize(array $raw, SyncRun $run): array
    {
        $games = [];
        $add = function (string $kind, string $slug, ?string $name, string $region, ?string $type, array $o, array $u = []) use (&$games) {
            $games[$slug] ??= ['slug' => $slug, 'kind' => $kind, 'type' => $type, 'name' => self::named($name), 'region' => $region, 'offers' => [], 'playtime' => null, 'lastPlayed' => null, 'achievements' => null];
            $games[$slug]['offers'][] = ['id' => $o['originOfferId'], 'name' => self::named($name), 'ownership' => $u['ownershipMethods'] ?? [], 'since' => $u['initialEntitlementDate'] ?? null];
        };

        $gameOffers = [];
        foreach ($raw['owned'] as $o) {
            $p = $o['product'] ?? [];
            if (empty($p['gameSlug']) || !empty($p['trialDetails']) || (!empty($p['baseItem']['gameType']) && $p['baseItem']['gameType'] !== 'BASE_GAME')) {
                continue;
            }
            $add('game', $p['gameSlug'], $p['name'] ?? null, self::regionOf($p['id'] ?? null), 'BASE_GAME', $o, $p['gameProductUser'] ?? []);
            $gameOffers[$o['originOfferId']] = true;
        }

        $left = [];
        foreach ($raw['addons'] as $o) {
            $p = $o['product'] ?? null;
            $l = $o['legacyOffer'] ?? null;
            if (isset($gameOffers[$o['originOfferId']])) {
                continue;
            }
            if (!empty($p['gameSlug']) && !preg_match(self::NOT_A_PACK, (string) $p['name'])) {
                $add('addon', $p['gameSlug'], $p['name'], self::regionOf($p['id'] ?? null), $p['baseItem']['gameType'] ?? null, $o, $p['gameProductUser'] ?? []);
            } elseif (!$p && ($l['displayType'] ?? null) === 'Expansion') {
                // with no product page, only an expansion is a pack (SimCity: Cities of Tomorrow); EA's legacy offer names it
                $add('addon', slugify(self::norm($l['displayName'])), $l['displayName'], 'PAL', 'EXPANSION_PACK', $o);
            } else {
                $left[] = clean_name($p['name'] ?? $l['displayName'] ?? $o['originOfferId']);
            }
        }

        $bySince = fn ($x, $y) => strcmp((string) $x['since'], (string) $y['since']);
        foreach ($games as &$g) {
            usort($g['offers'], $bySince);
            if ($g['kind'] === 'addon') {
                continue;
            }
            $t = $raw['playTime'][$g['slug']] ?? null;
            // minutes; a game the EA app has no session of was never played in it
            $g['playtime'] = $t ? (int) round(($t['totalPlayTimeSeconds'] ?? 0) / 60) : 0;
            $g['lastPlayed'] = $t['lastSessionEndDate'] ?? null;
            $sets = array_unique(array_filter(array_map(fn ($o) => $raw['achievements']['offerToSet'][$o['id']] ?? null, $g['offers'])));
            $list = array_merge(...array_map(fn ($s) => $raw['achievements']['sets'][$s] ?? [], array_values($sets)) ?: [[]]);
            if ($list) {
                // a locked achievement's date is only when it was asked for
                $unlocked = array_values(array_map(fn ($a) => ['name' => clean_name($a['name']), 'date' => $a['date'] ?? null], array_filter($list, fn ($a) => ($a['awardCount'] ?? 0) > 0)));
                usort($unlocked, fn ($x, $y) => strcmp((string) $y['date'], (string) $x['date']));
                $g['achievements'] = ['total' => count($list), 'earned' => count($unlocked), 'unlocked' => $unlocked];
            }
        }
        unset($g);

        $left = array_values(array_unique($left));
        sort($left);
        $all = array_values($games);

        return [
            'account' => [
                'synced' => $raw['exported_at_utc'],
                'source' => 'ea',
                'totals' => [
                    'games' => count(array_filter($all, fn ($g) => $g['kind'] === 'game')),
                    'addons' => count(array_filter($all, fn ($g) => $g['kind'] === 'addon')),
                    'playtime' => array_sum(array_map(fn ($g) => $g['playtime'] ?? 0, $all)),
                ],
                'left' => $left,
            ],
            'collections' => ['games' => $games],
        ];
    }

    public function merge(array $data, SyncRun $run): void
    {
        $ea = $data['collections']['games'];
        $tied = [];
        foreach ($run->games as $g) {
            if (!empty($g['eaSlug'])) {
                $tied[$g['eaSlug']] = true;
            }
        }

        // the games first, so an add-on added after finds its game
        $order = array_values($ea);
        usort($order, fn ($a, $b) => ($a['kind'] === 'game' ? 0 : 1) <=> ($b['kind'] === 'game' ? 0 : 1));

        foreach ($order as $game) {
            if (isset($tied[$game['slug']])) {
                continue;
            }
            $cs = $this->candidates($run, $game);
            if (count($cs) === 1) {
                $run->link(array_key_first($cs), 'eaSlug', $game['slug'], $game['name']);
            } elseif ($cs) {
                $run->report('ambiguous', 'games', $game['slug'], $game['name'], ids_of($cs));
            } else {
                $run->add($this->newCopy($run, $ea, $game), '-ea', $game['name']);
            }
            $tied[$game['slug']] = true;
        }

        foreach ($run->games as $id => $g) {
            if (!empty($g['eaSlug']) && !isset($ea[$g['eaSlug']])) {
                $run->change('missing', $id, 'eaSlug', null, $g['eaSlug'], $g['title'], 'Not in the EA app library any more; the copy stays tied.');
            }
        }
    }

    /** A PC copy not tied yet with the same name; of several, the ones in a launcher's library. */
    private function candidates(SyncRun $run, array $game): array
    {
        $ns = array_unique(array_map([self::class, 'norm'], [$game['name'], ...array_column($game['offers'], 'name')]));
        $same = array_filter($run->games, fn ($g) => $g['console'] === 'pc' && empty($g['hidden']) && empty($g['eaSlug'])
            && array_intersect(names_of($g, [self::class, 'norm']), $ns));
        if (count($same) < 2) {
            return $same;
        }
        $inApp = array_filter($same, fn ($g) => ($g['format'] ?? '') === 'digital' || !empty($g['alsoDigital']));

        return $inApp ?: $same;
    }

    /** An add-on's game: the tied game copy whose title its name starts with ("The Sims 4 Grunge Revival Kit": The Sims 4). */
    private function baseOf(SyncRun $run, array $ea, array $game): ?array
    {
        $n = self::norm($game['name']);
        $bases = array_filter($run->games, fn ($g) => $g['console'] === 'pc' && !empty($g['eaSlug']) && ($ea[$g['eaSlug']]['kind'] ?? null) === 'game'
            && str_starts_with($n, self::norm($g['title']) . ' '));
        usort($bases, fn ($a, $b) => strlen($b['title']) <=> strlen($a['title']));

        return $bases[0] ?? null;
    }

    private function newCopy(SyncRun $run, array $ea, array $game): array
    {
        $base = $game['kind'] === 'addon' ? $this->baseOf($run, $ea, $game) : null;
        // an add-on reads as its game's: "The Sims 4: Grunge Revival Kit"
        $title = $base
            ? $base['title'] . ': ' . preg_replace(['/^[\s:]+/', '/\s+Expansion Pack$/i'], '', mb_substr(clean_name($game['name']), mb_strlen($base['title'])))
            : $game['name'];
        $pack = preg_match('/\bkit$/i', $game['name']) ? 'Kit' : (preg_match('/\bstuff$/i', $game['name']) ? 'Stuff Pack'
            : ($game['type'] === 'EXPANSION_PACK' ? 'Expansion Pack' : 'Extra'));

        return array_filter([
            'id' => 'pc-' . slugify($title),
            'console' => 'pc',
            'title' => $title,
            'region' => $game['region'] === 'PAL' && $base ? $base['region'] : $game['region'],
            'released' => null,
            'publisher' => $base['publisher'] ?? null,
            'developer' => $base['developer'] ?? null,
            'genre' => $base['genre'] ?? null,
            'format' => 'digital',
            'launcher' => 'ea',
            'series' => $base['series'] ?? null,
            'pack' => $game['kind'] === 'addon' ? $pack : null,
            'eaSlug' => $game['slug'],
        ], fn ($v) => $v !== null);
    }
}
