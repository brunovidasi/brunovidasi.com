<?php

/**
 * Find cover: box art for a copy, from free sources that need no key. Was
 * tools/fetch-covers.mjs. Where the old tool took the first match, the admin is
 * shown every match, best first, and picks one; apply_cover() then saves it.
 *
 *   libretro-thumbnails   front box scans named after the release, region by region
 *   aldostools/resources  PS3 covers named by disc serial (BLES…, BLUS…), with titleid.txt
 *   xlenore/ps2-covers    PS2 covers named by disc serial, when coverMatch is one
 *   The Sims Wiki         PC games: the box in the infobox of the game's page
 *   Wikipedia             PC games, and what the others lack on the newer consoles
 *   TheGamesDB            real box scans, region by region, for PS4, PS5, Xbox One, Xbox 360, Switch, Switch 2 and PC
 *
 * A copy steers it with coverMatch (a libretro file name, a PS3 or PS2 serial,
 * or a wiki page), tgdbId (a TheGamesDB entry) and wikiCover: false (no Wikipedia).
 * The file lists of the GitHub collections are kept a week in data/cache.
 */

const COVER_UA = 'BrunosGameShelf-cover-finder/1.0 (https://github.com/brunovidasi/games-collection)';

const LIBRETRO = [
    'ps1' => 'Sony_-_PlayStation', 'ps2' => 'Sony_-_PlayStation_2', 'ps3' => 'Sony_-_PlayStation_3',
    'xbox' => 'Microsoft_-_Xbox', 'x360' => 'Microsoft_-_Xbox_360',
    'wii' => 'Nintendo_-_Wii', 'wiiu' => 'Nintendo_-_Wii_U',
    'ds' => 'Nintendo_-_Nintendo_DS', '3ds' => 'Nintendo_-_Nintendo_3DS',
    'gb' => 'Nintendo_-_Game_Boy', 'gbc' => 'Nintendo_-_Game_Boy_Color', 'gba' => 'Nintendo_-_Game_Boy_Advance',
    'snes' => 'Nintendo_-_Super_Nintendo_Entertainment_System',
    'dos' => 'DOS', // PC games from the DOS days, such as the first Grand Theft Auto
];

// what each region code plays on (as in js/consoles.js)
const REGION_STD = ['AUS' => 'PAL', 'EUR' => 'PAL', 'HOL' => 'PAL', 'PAL' => 'PAL', 'UK' => 'PAL', 'USA' => 'NTSC-U', 'NTSC-U' => 'NTSC-U', 'JPN' => 'NTSC-J', 'NTSC-J' => 'NTSC-J'];

const TGDB_PLATFORM = ['ps4' => 4919, 'ps5' => 4980, 'xone' => 4920, 'x360' => 15, 'switch' => 4971, 'switch2' => 5021, 'pc' => 1];

// the consoles none of the scan collections have: Wikipedia's box, often key art rather than a scan
const WIKIPEDIA_CONSOLES = ['ps4', 'ps5', 'xone', 'x360', 'switch', 'switch2'];

// Wikipedia's PS5 and Switch 2 pictures are the game's key art, without the console's band: the site prints it over them
const PLAIN_ART = ['ps5', 'switch2'];

/** The looser name the cover sources are matched on: "Billy & Mandy", "Billy _ Mandy" and "Billy and Mandy" agree. */
function cover_norm(string $s): string
{
    $s = mb_strtolower(strip_accents(preg_replace('/[™®©]/u', ' ', $s)));
    $s = preg_replace(["/['’`]/u", '/[^a-z0-9]+/', '/\band\b/', '/\s+/'], ['', ' ', ' ', ' '], $s);

    return preg_replace('/^the /', '', trim($s));
}

/* ---------- cached lists from GitHub ---------- */

function cover_cache(string $name, int $ttl, callable $build): mixed
{
    $dir = data_dir() . '/cache';
    $file = "$dir/" . preg_replace('/[^a-z0-9_.-]+/i', '_', $name) . '.json';
    if (is_file($file) && filemtime($file) > time() - $ttl) {
        $data = json_decode((string) file_get_contents($file), true);
        if ($data !== null) {
            return $data;
        }
    }
    $data = $build();
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    @file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    return $data;
}

/** The file names in a folder of a GitHub repo, without downloading it. */
function github_folder(string $owner, string $repo, string $folder): array
{
    return cover_cache("gh_{$owner}_{$repo}_{$folder}", 7 * 86400, function () use ($owner, $repo, $folder) {
        $data = Http::getJson("https://api.github.com/repos/$owner/$repo/git/trees/HEAD:" . rawurlencode($folder), ['Accept: application/vnd.github+json'], ['user_agent' => COVER_UA, 'timeout' => 90]);
        return array_values(array_map(fn ($t) => $t['path'], array_filter($data['tree'] ?? [], fn ($t) => $t['type'] === 'blob')));
    });
}

/* ---------- libretro-thumbnails ---------- */

/** "Chronicles of Narnia, The - The Lion… (Europe) (En,Fr)" → [stem, base, tags] */
function parse_libretro(string $file): array
{
    $stem = preg_replace('/\.png$/i', '', $file);
    $i = strpos($stem, ' (');
    $base = $i === false ? $stem : substr($stem, 0, $i);
    if (preg_match('/^(.*?), (The|A|An)( - .*)?$/', $base, $m)) {
        $base = "$m[2] $m[1]" . ($m[3] ?? '');
    }
    preg_match_all('/\(([^)]*)\)/', $stem, $tags);

    return ['stem' => $stem, 'base' => cover_norm($base), 'tags' => $tags[1]];
}

/** How well a scan's region tags suit a copy: 0 is its own box, 5 and up another region's, 99 never. */
function region_rank(array $tags, ?string $region): float
{
    $t = implode(' | ', $tags);
    if (preg_match('/\b(beta|proto|prototype|demo|sample|kiosk|preview|trade|promo|unl|pirate|hack)\b/i', $t)) {
        return 99;
    }
    $euOther = '/\b(France|Germany|Spain|Italy|Netherlands|Scandinavia|Sweden|Russia|Poland|Portugal|Greece|Brazil|Korea|China|Taiwan|Asia)\b/';
    $has = fn (string $w) => (bool) preg_match('/\b' . $w . '\b/', $t);
    $std = REGION_STD[$region] ?? 'PAL';

    if ($std === 'NTSC-J') {
        $r = $has('Japan') ? 0 : ($has('World') ? 3 : ($has('USA') ? 5 : 8));
    } elseif ($std === 'NTSC-U') {
        $r = $has('USA') ? ($has('Europe') ? 1 : 0) : ($has('World') ? 2 : ($has('Canada') ? 3 : ($has('Europe') ? 5 : 8)));
    } elseif ($region === 'HOL' && $has('Netherlands')) {
        $r = 0;
    } elseif ($region === 'EUR' || $region === 'HOL') {
        // a European copy: the European box first, an Australian one is next best
        $r = $has('Europe') ? (preg_match($euOther, $t) ? 4 : 0.5) : ($has('UK') ? 1 : ($has('Australia') ? 2 : ($has('World') ? 3 : (preg_match($euOther, $t) ? 6 : ($has('USA') ? 5 : 8)))));
    } else {
        // an Australian copy, or a PAL one: the Australian box first
        $r = $has('Australia') ? 0 : ($has('Europe') ? (preg_match($euOther, $t) ? 4 : 1) : ($has('UK') ? 2 : ($has('World') ? 3 : (preg_match($euOther, $t) ? 6 : ($has('USA') ? 5 : 8)))));
    }
    if (preg_match('/Virtual Console|Switch Online|Collection/', $t)) {
        $r += 0.5;
    }
    if (preg_match('/Rev \d|v\d/', $t)) {
        $r += 0.1;
    }

    return $r;
}

function find_libretro(array $g, string $sys): array
{
    $repo = LIBRETRO[$sys] ?? null;
    if (!$repo) {
        return [];
    }
    $files = github_folder('libretro-thumbnails', $repo, 'Named_Boxarts');
    $url = fn ($f) => "https://raw.githubusercontent.com/libretro-thumbnails/$repo/HEAD/Named_Boxarts/" . rawurlencode($f);

    if (!empty($g['coverMatch'])) {
        foreach ($files as $f) {
            if (preg_replace('/\.png$/i', '', $f) === $g['coverMatch']) {
                return [['url' => $url($f), 'source' => 'libretro-thumbnails', 'ref' => $g['coverMatch'], 'rank' => 0]];
            }
        }
        return [];
    }

    $want = cover_norm($g['title']);
    $hits = [];
    foreach ($files as $f) {
        $p = parse_libretro($f);
        if ($p['base'] === $want) {
            $rank = region_rank($p['tags'], $g['region'] ?? null);
            if ($rank < 99) {
                $hits[] = ['url' => $url($p['stem'] . '.png'), 'source' => 'libretro-thumbnails', 'ref' => $p['stem'], 'rank' => $rank + strlen($p['stem']) / 1000, 'offRegion' => $rank >= 5];
            }
        }
    }

    return $hits;
}

/* ---------- PS3 and PS2 by serial ---------- */

function find_ps3(array $g): array
{
    $covers = [];
    foreach (github_folder('aldostools', 'resources', 'COV') as $f) {
        $covers[strtoupper($f)] = $f;
    }
    $url = fn ($f) => 'https://raw.githubusercontent.com/aldostools/resources/HEAD/COV/' . rawurlencode($f);

    if (!empty($g['coverMatch'])) {
        $f = $covers[strtoupper($g['coverMatch']) . '.JPG'] ?? null;
        return $f ? [['url' => $url($f), 'source' => 'aldostools/resources', 'ref' => $g['coverMatch'], 'rank' => 0]] : [];
    }

    $titles = cover_cache('ps3_titleid', 7 * 86400, function () {
        $r = Http::get('https://raw.githubusercontent.com/aldostools/resources/HEAD/titleid.txt', [], ['user_agent' => COVER_UA, 'timeout' => 90]);
        $rows = [];
        foreach (preg_split('/\r?\n/', $r->body) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            [$id, $title] = array_pad(explode(' ', $line, 2), 2, '');
            // "Hitman: Absolution™ [EUR]" → key "hitman absolution", tag "EUR"
            preg_match('/\[([^\]]*)\]/', $title, $tag);
            $tag = $tag[1] ?? '';
            $t = preg_replace('/\s*\[[^\]]*\]/', '', $title);
            if (preg_match('/^(.*?), (The|A|An)( - .*| [:\-–].*)?$/u', $t, $m)) {
                $t = "$m[2] $m[1]" . ($m[3] ?? '');
            }
            $rank = $tag === '' ? 0 : (preg_match('/^(EUR|ENG|UK|EN)$/i', $tag) ? 0.5 : (preg_match('/edition|platinum|essentials|greatest|classics|pack/i', $tag) ? 2 : 5));
            $rows[] = ['id' => $id, 'title' => $title, 'key' => cover_norm($t), 'rank' => $rank];
        }
        return $rows;
    });

    $want = cover_norm($g['title']);
    $std = REGION_STD[$g['region'] ?? ''] ?? 'PAL';
    $order = $std === 'NTSC-U' ? ['BCUS', 'BLUS', 'NPUA', 'NPUB'] : ($std === 'NTSC-J' ? ['BCJS', 'BLJS', 'BLJM', 'NPJA', 'NPJB'] : ['BCES', 'BLES', 'NPEA', 'NPEB']);
    $hits = [];
    foreach ($titles as $row) {
        if ($row['key'] !== $want || !($f = $covers[strtoupper($row['id']) . '.JPG'] ?? null)) {
            continue;
        }
        $pre = array_search(substr($row['id'], 0, 4), $order, true);
        $hits[] = ['url' => $url($f), 'source' => 'aldostools/resources', 'ref' => "{$row['id']} · {$row['title']}",
            'rank' => ($pre === false ? 6 : $pre) + $row['rank'] / 10, 'offRegion' => $pre === false];
    }

    return $hits;
}

function find_ps2_serial(array $g): array
{
    if (!preg_match('/^S[CL][EUP][SMD]-\d{5}$/', $g['coverMatch'] ?? '')) {
        return [];
    }

    return [['url' => "https://raw.githubusercontent.com/xlenore/ps2-covers/HEAD/covers/default/{$g['coverMatch']}.jpg", 'source' => 'xlenore/ps2-covers', 'ref' => $g['coverMatch'], 'rank' => 0]];
}

/* ---------- the wikis ---------- */

/**
 * The infobox's "image = [[File:The Sims 2 Apartment Life Cover.jpg|250px]]". A page with
 * several boxes puts them in tabs: a box on the shelf takes the original one, a pack in the
 * EA app the first, which is today's art.
 */
function infobox_file(string $text, bool $digital): ?string
{
    if (!preg_match('/\|\s*image\s*=\s*<tabber>([\s\S]*?)<\/tabber>/i', $text, $tabs)) {
        return preg_match('/\|\s*image\s*=\s*\[\[\s*(?:File|Image)\s*:\s*([^|\]]+)/i', $text, $m) ? trim($m[1]) : null;
    }
    $files = [];
    foreach (explode('|-|', $tabs[1]) as $t) {
        if (preg_match('/\[\[\s*(?:File|Image)\s*:\s*([^|\]]+)/i', $t, $m)) {
            $files[] = ['name' => trim(explode('=', $t)[0]), 'file' => trim($m[1])];
        }
    }
    if (!$files) {
        return null;
    }
    if ($digital) {
        return $files[0]['file'];
    }
    foreach ($files as $f) {
        if (preg_match('/original|1st|first/i', $f['name'])) {
            return $f['file'];
        }
    }

    return end($files)['file'];
}

function find_sims_wiki(string $page, array $g): array
{
    $api = 'https://sims.fandom.com/api.php';
    $j = Http::get("$api?action=parse&format=json&redirects=1&prop=wikitext&section=0&page=" . rawurlencode($page), [], ['user_agent' => COVER_UA, 'retries' => 1])->json();
    $text = $j['parse']['wikitext']['*'] ?? null;
    if (!$text || !($file = infobox_file($text, ($g['format'] ?? '') === 'digital'))) {
        return [];
    }
    $info = Http::get("$api?action=query&format=json&prop=imageinfo&iiprop=url&titles=" . rawurlencode('File:' . $file), [], ['user_agent' => COVER_UA, 'retries' => 1])->json();
    $pages = $info['query']['pages'] ?? [];
    $url = $pages ? (reset($pages)['imageinfo'][0]['url'] ?? null) : null;

    return $url ? [['url' => $url, 'source' => 'sims.fandom.com', 'ref' => "{$j['parse']['title']} · $file", 'rank' => 0]] : [];
}

function find_wikipedia(string $page, float $rank = 1): array
{
    $j = Http::get('https://en.wikipedia.org/w/api.php?action=query&format=json&redirects=1&prop=pageimages&piprop=original&pilicense=any&titles=' . rawurlencode($page), [], ['user_agent' => COVER_UA, 'retries' => 1])->json();
    foreach ($j['query']['pages'] ?? [] as $p) {
        $o = $p['original'] ?? null;
        // a box is a scan, never a drawing (an .svg is the game's logo), and taller than it is wide
        if ($o && !preg_match('/\.svg(\?|$)/i', $o['source']) && !($o['width'] / max(1, $o['height']) > 0.9)) {
            return [['url' => $o['source'], 'source' => 'en.wikipedia.org', 'ref' => $p['title'] . ' · ' . rawurldecode(basename($o['source'])), 'rank' => $rank]];
        }
    }

    return [];
}

/* ---------- TheGamesDB ---------- */

function find_tgdb(array $g, string $sys): array
{
    if (!isset(TGDB_PLATFORM[$sys])) {
        return [];
    }
    $box = fn ($id) => "https://cdn.thegamesdb.net/images/original/boxart/front/$id-1.jpg";
    if (!empty($g['tgdbId'])) {
        return [['url' => $box($g['tgdbId']), 'source' => 'thegamesdb.net', 'ref' => "TheGamesDB #{$g['tgdbId']}", 'rank' => 0]];
    }

    $r = Http::get('https://thegamesdb.net/search.php?name=' . rawurlencode($g['title']) . '&platform_id%5B%5D=' . TGDB_PLATFORM[$sys], [], ['user_agent' => COVER_UA, 'retries' => 1]);
    if (!$r->ok()) {
        return [];
    }
    $want = cover_norm($g['title']);
    $std = REGION_STD[$g['region'] ?? ''] ?? 'PAL';
    $hits = [];
    // one card per release; a card without a box scan is passed over
    foreach (array_slice(explode('<div class="col-6 col-md-2">', $r->body), 1) as $card) {
        if (!preg_match('/game\.php\?id=(\d+)[\s\S]*?boxart\/front\/\d+-1\.jpg[\s\S]*?<p>([\s\S]*?)<\/p>([\s\S]*?)<p class="text-muted">/', $card, $m)) {
            continue;
        }
        $title = trim(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5));
        $edition = (bool) preg_match('/\[[^\]]*\]/', $title);
        if (cover_norm(preg_replace('/\[[^\]]*\]/', '', $title)) !== $want) {
            continue;
        }
        $region = trim(preg_replace(['/<[^>]+>/', '/\d{4}-\d{2}-\d{2}/', '/\s+/'], [' ', '', ' '], $m[3]));
        $pal = (bool) preg_match('/\bPAL\b/', $region);
        $ntsc = (bool) preg_match('/\bNTSC/', $region);
        $english = $region === '' || preg_match('/United Kingdom|Australia|United States|Canada|^\s*(PAL|NTSC(-U)?)\s*$/', $region);
        // (an English box from elsewhere before a PAL one in another language)
        $rank = match ($std) {
            'PAL' => $pal && $english ? 0 : ($region === '' ? 1.5 : ($english ? 2 : ($pal ? 2.2 : 3))),
            'NTSC-U' => $ntsc && $english ? 0 : ($region === '' ? 1 : ($ntsc ? 2 : 3)),
            default => preg_match('/Japan|NTSC-J/', $region) ? 0 : 1,
        } + ($edition && empty($g['edition']) ? 3 : 0);
        $hits[] = ['url' => $box($m[1]), 'source' => 'thegamesdb.net', 'ref' => "$title · " . ($region ?: 'no region') . " · TheGamesDB #{$m[1]}", 'rank' => $rank, 'offRegion' => $rank >= 2];
    }

    return $hits;
}

/* ---------- all of them ---------- */

/**
 * Every cover the sources have for a copy, best first: [url, source, ref, rank, offRegion].
 * A source that can't be reached is passed over, and named in $errors.
 */
function find_covers(array $g, array &$errors = []): array
{
    $sys = $g['caseStyle'] ?? $g['console'];
    $tries = [];

    if ($sys === 'ps2') {
        $tries[] = fn () => find_ps2_serial($g);
    }
    if ($sys === 'ps3') {
        $tries[] = fn () => find_ps3($g);
    }
    if ($sys === 'pc') {
        $page = $g['coverMatch'] ?? $g['title'];
        $tries[] = fn () => find_sims_wiki($page, $g);
        $tries[] = fn () => find_wikipedia($page, 0.5);
    }
    $tries[] = fn () => find_libretro($g, $sys === 'pc' ? 'dos' : $sys);
    if (in_array($sys, WIKIPEDIA_CONSOLES, true) || $sys === 'pc') {
        $tries[] = fn () => find_tgdb($g, $sys);
    }
    if (in_array($sys, WIKIPEDIA_CONSOLES, true) && ($g['wikiCover'] ?? null) !== false) {
        $tries[] = function () use ($g) {
            foreach (!empty($g['coverMatch']) ? [$g['coverMatch']] : [$g['title'], $g['title'] . ' (video game)'] as $page) {
                if ($hit = find_wikipedia($page, 4)) {
                    return $hit;
                }
            }
            return [];
        };
    }

    $hits = [];
    $order = 0;
    foreach ($tries as $try) {
        try {
            foreach ($try() as $hit) {
                $hit['order'] = $order++;
                $hits[$hit['url']] ??= $hit;
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
    $hits = array_values($hits);
    usort($hits, fn ($a, $b) => [$a['rank'], $a['order']] <=> [$b['rank'], $b['order']]);

    return array_slice($hits, 0, 16);
}

/**
 * Saves a picture as a copy's cover and returns the copy with its new cover and
 * colours. A sticker cut from a photo of the copy itself (cover.label) and the
 * other pictures of it stay with the new scan.
 */
function apply_cover(array $game, string $bytes, string $source, ?string $ref): array
{
    [$w, $h] = image_size($bytes);
    // a box is taller than it is wide (a DS or 3DS case a little wider than tall, a SNES box wide, 180 by 128 mm):
    // a wider picture found online is a photo of the box, or of something else
    $limit = ['ds' => 1.2, '3ds' => 1.2, 'snes' => 1.6][$game['console']] ?? 0.95;
    if (!in_array($source, ['manual', 'steam'], true) && $w / max(1, $h) > $limit) {
        throw new RuntimeException('That picture is wider than a box, so it is a photo of something else.');
    }

    $saved = save_cover_image($bytes, cover_path_for($game));
    $old = $game['cover'] ?? [];
    $cover = ['file' => $saved['file'], 'ratio' => $saved['ratio'], 'source' => $source];
    if ($ref !== null && $ref !== '') {
        $cover['ref'] = $ref;
    }
    foreach (['label', 'labelRef', 'back', 'spine', 'side', 'top', 'parts'] as $keep) {
        if (isset($old[$keep])) {
            $cover[$keep] = $old[$keep];
        }
    }
    if ($source === 'en.wikipedia.org' && in_array($game['caseStyle'] ?? $game['console'], PLAIN_ART, true)) {
        $cover['plain'] = true;
    }

    $game['cover'] = $cover;
    $game['colors'] = $saved['colors'];

    return $game;
}

/** Downloads a picture for apply_cover(), only from http(s), at most 15 MB. */
function download_image(string $url): string
{
    if (!safe_http_url($url)) {
        throw new RuntimeException('Only http and https addresses can be fetched.');
    }
    $r = Http::get($url, [], ['user_agent' => COVER_UA, 'timeout' => 60]);
    if (!$r->ok()) {
        throw new RuntimeException("HTTP {$r->status} from " . parse_url($url, PHP_URL_HOST));
    }
    if (strlen($r->body) > 15 << 20) {
        throw new RuntimeException('That picture is over 15 MB.');
    }

    return $r->body;
}
