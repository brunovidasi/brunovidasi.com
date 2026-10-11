<?php
declare(strict_types=1);

/**
 * PSN gaming-history exporter using tustin/psn-php.
 *
 * Run from the project directory:
 *   PSN_NPSSO='your-new-npsso-token' php export.php
 *
 * Never commit your NPSSO token or the exports directory to a public repository.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this exporter from the command line; see README.md.\n");
}

require __DIR__ . '/vendor/autoload.php';

use Tustin\PlayStation\Client;

date_default_timezone_set('UTC');

$token = getenv('PSN_NPSSO');
if ($token === false || $token === '') {
    fwrite(STDERR, "Missing PSN_NPSSO environment variable.\n");
    fwrite(STDERR, "Set it to a newly generated NPSSO token, then run: php export.php\n");
    exit(1);
}

$outDir = __DIR__ . '/exports';
if (!is_dir($outDir) && !mkdir($outDir, 0700, true) && !is_dir($outDir)) {
    throw new RuntimeException("Could not create export directory: {$outDir}");
}

$errors = [];
$written = [];

function jsonSafe(mixed $value): mixed {
    if (is_object($value)) {
        if ($value instanceof JsonSerializable) {
            return jsonSafe($value->jsonSerialize());
        }
        $result = [];
        foreach (get_object_vars($value) as $key => $item) {
            $result[$key] = jsonSafe($item);
        }
        return $result;
    }
    if (is_array($value)) {
        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = jsonSafe($item);
        }
        return $result;
    }
    if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
    return $value;
}

function saveJson(string $name, mixed $data): void {
    global $outDir, $written;
    $path = $outDir . '/' . $name . '.json';
    $json = json_encode(jsonSafe($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        throw new RuntimeException("JSON encoding failed for {$name}: " . json_last_error_msg());
    }
    if (file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException("Could not write {$path}");
    }
    $written[$name] = ['file' => basename($path), 'bytes' => filesize($path)];
    echo "Saved {$name}.json\n";
}

function callIfAvailable(object $object, string $method): mixed {
    if (!method_exists($object, $method)) {
        return null;
    }
    try {
        return $object->{$method}();
    } catch (Throwable $e) {
        return ['_error' => $e->getMessage()];
    }
}

function objectData(object $object, array $methods): array {
    $data = ['_class' => get_class($object)];
    foreach ($methods as $method) {
        if (method_exists($object, $method)) {
            try {
                $data[$method] = jsonSafe($object->{$method}());
            } catch (Throwable $e) {
                $data[$method] = ['_error' => $e->getMessage()];
            }
        }
    }
    // Include fetched API payloads where the model exposes fetch().
    if (method_exists($object, 'fetch')) {
        try {
            $data['_raw'] = jsonSafe($object->fetch());
        } catch (Throwable $e) {
            $data['_fetch_error'] = $e->getMessage();
        }
    }
    return $data;
}

function collectIterator(iterable $items, callable $mapper, string $label): array {
    $rows = [];
    $count = 0;
    foreach ($items as $item) {
        try {
            $rows[] = $mapper($item);
        } catch (Throwable $e) {
            $rows[] = ['_error' => $e->getMessage()];
        }
        $count++;
        if ($count % 50 === 0) {
            echo "  {$label}: {$count} items...\n";
        }
    }
    echo "  {$label}: {$count} items collected.\n";
    return $rows;
}

$client = new Client();

try {
    $client->loginWithNpsso($token);
    echo "Authenticated with PSN.\n";

    $user = $client->users()->me();

    // Profile and account-level trophy summary.
    $profile = [
        'online_id' => callIfAvailable($user, 'onlineId'),
        'account_id' => callIfAvailable($user, 'accountId'),
        'about_me' => callIfAvailable($user, 'aboutMe'),
        'avatar_url' => callIfAvailable($user, 'avatarUrl'),
        'avatar_urls' => callIfAvailable($user, 'avatarUrls'),
        'follower_count' => callIfAvailable($user, 'followerCount'),
        'languages' => callIfAvailable($user, 'languages'),
        'has_plus' => callIfAvailable($user, 'hasPlus'),
        'is_verified' => callIfAvailable($user, 'isVerified'),
        'is_online' => callIfAvailable($user, 'isOnline'),
        'raw_profile' => callIfAvailable($user, 'fetch'),
    ];
    saveJson('profile', $profile);

    try {
        $summary = $user->trophySummary();
        $summaryData = [
            'level' => callIfAvailable($summary, 'level'),
            'tier' => callIfAvailable($summary, 'tier'),
            'progress' => callIfAvailable($summary, 'progress'),
            'bronze' => callIfAvailable($summary, 'bronze'),
            'silver' => callIfAvailable($summary, 'silver'),
            'gold' => callIfAvailable($summary, 'gold'),
            'platinum' => callIfAvailable($summary, 'platinum'),
            'raw' => callIfAvailable($summary, 'fetch'),
        ];
        saveJson('trophy-summary', $summaryData);
    } catch (Throwable $e) {
        $errors['trophy-summary'] = $e->getMessage();
    }

    // Game history, including play duration and first/last played timestamps.
    try {
        $games = collectIterator(
            $user->gameList()->getIterator(),
            fn($game) => objectData($game, [
                'id', 'name', 'localizedName', 'category', 'service',
                'playDuration', 'playCount', 'firstPlayedDateTime',
                'lastPlayedDateTime', 'imageUrl', 'localizedImageUrl',
                'stats', 'media'
            ]),
            'Games'
        );
        saveJson('games', $games);
    } catch (Throwable $e) {
        $errors['games'] = $e->getMessage();
    }

    // Trophy title list and earned/defined counts per game.
    $titles = [];
    try {
        $titles = collectIterator(
            $user->trophyTitles()->getIterator(),
            fn($title) => objectData($title, [
                'name', 'detail', 'iconUrl', 'platform', 'hasTrophies',
                'hasPlatinum', 'trophyCount', 'bronzeTrophyCount',
                'silverTrophyCount', 'goldTrophyCount', 'npCommunicationId',
                'trophySetVersion', 'lastUpdatedDateTime',
                'earnedTrophiesBronzeCount', 'earnedTrophiesSilverCount',
                'earnedTrophiesGoldCount', 'earnedTrophiesPlatinumCount',
                'progress', 'serviceName'
            ]),
            'Trophy titles'
        );
        saveJson('trophy-titles', $titles);
    } catch (Throwable $e) {
        $errors['trophy-titles'] = $e->getMessage();
    }

    // Detailed trophy records, grouped by title and trophy group.
    // Failures are recorded per title/group so one unsupported endpoint won't stop the export.
    $detailedTrophies = [];
    try {
        foreach ($user->trophyTitles()->getIterator() as $title) {
            $titleRow = [
                'name' => callIfAvailable($title, 'name'),
                'np_communication_id' => callIfAvailable($title, 'npCommunicationId'),
                'service_name' => callIfAvailable($title, 'serviceName'),
                'groups' => [],
            ];
            try {
                foreach ($title->trophyGroups()->getIterator() as $group) {
                    $groupRow = [
                        'id' => callIfAvailable($group, 'id'),
                        'name' => callIfAvailable($group, 'name'),
                        'detail' => callIfAvailable($group, 'detail'),
                        'icon_url' => callIfAvailable($group, 'iconUrl'),
                        'trophies' => [],
                    ];
                    try {
                        foreach ($group->trophies()->getIterator() as $trophy) {
                            $groupRow['trophies'][] = objectData($trophy, [
                                'id', 'name', 'detail', 'type', 'earnedRate',
                                'hidden', 'iconUrl', 'progressTargetValue',
                                'rewardName', 'rewardImageUrl', 'earned',
                                'earnedDateTime', 'progress', 'progressRate',
                                'progressedDateTime'
                            ]);
                        }
                    } catch (Throwable $e) {
                        $groupRow['_trophies_error'] = $e->getMessage();
                    }
                    $titleRow['groups'][] = $groupRow;
                }
            } catch (Throwable $e) {
                $titleRow['_groups_error'] = $e->getMessage();
            }
            $detailedTrophies[] = $titleRow;
            if (count($detailedTrophies) % 25 === 0) {
                echo "  Detailed trophy titles: " . count($detailedTrophies) . "...\n";
            }
        }
        saveJson('trophies-detailed', $detailedTrophies);
    } catch (Throwable $e) {
        $errors['trophies-detailed'] = $e->getMessage();
    }

    // Friends list. Availability depends on PSN privacy settings and current API access.
    try {
        $friends = collectIterator(
            $user->friends()->getIterator(),
            fn($friend) => objectData($friend, [
                'onlineId', 'accountId', 'aboutMe', 'avatarUrl',
                'avatarUrls', 'isOnline', 'isVerified', 'hasPlus',
                'followerCount', 'languages'
            ]),
            'Friends'
        );
        saveJson('friends', $friends);
    } catch (Throwable $e) {
        $errors['friends'] = $e->getMessage();
    }

    // Include a compact export manifest and any partial failures.
    saveJson('export-summary', [
        'created_at_utc' => gmdate('c'),
        'online_id' => $profile['online_id'] ?? null,
        'account_id' => $profile['account_id'] ?? null,
        'files' => $written,
        'errors' => $errors,
        'notes' => [
            'This is an unofficial exporter using the tustin/psn-php library.',
            'PSN does not expose every account field through this library.',
            'Purchase history, console hardware details, and exact playtime may not be available from these endpoints.',
            'A field may be absent or empty because of privacy settings or Sony API changes.'
        ],
    ]);

    echo "\nExport finished. Check the exports/ directory.\n";
    if ($errors) {
        echo "Some categories failed. Details are in exports/export-summary.json.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Export failed: " . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, "Check your NPSSO token and the library's current compatibility with PSN.\n");
    exit(1);
}
