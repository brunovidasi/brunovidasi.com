<?php

function all_artists(): array
{
    return db()->query('SELECT * FROM artists ORDER BY position, name')->fetchAll();
}

function published_artists(): array
{
    return db()->query('SELECT id, slug, name, hero_cover FROM artists WHERE is_published = 1 ORDER BY position, name')->fetchAll();
}

function artist_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM artists WHERE slug = ?');
    $stmt->execute([$slug]);

    return $stmt->fetch() ?: null;
}

function artist_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM artists WHERE id = ?');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

/** The artist whose page this slug is, or null if there is no such page to show. */
function published_artist(string $slug): ?array
{
    $artist = artist_by_slug($slug);

    return $artist !== null && $artist['is_published'] ? $artist : null;
}

function eras_for_artist(int $artistId): array
{
    $stmt = db()->prepare('SELECT * FROM eras WHERE artist_id = ? ORDER BY position, name');
    $stmt->execute([$artistId]);

    return $stmt->fetchAll();
}

function era_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM eras WHERE id = ?');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

function era_rules(int $eraId): array
{
    $stmt = db()->prepare('SELECT * FROM era_rules WHERE era_id = ? ORDER BY rank, id');
    $stmt->execute([$eraId]);

    return $stmt->fetchAll();
}

/** How many records sit in each era of an artist, keyed by era id. */
function era_counts(int $artistId): array
{
    $stmt = db()->prepare("
        SELECT era_id, COUNT(*) AS n
          FROM items
         WHERE artist_id = ? AND era_id IS NOT NULL AND source = 'collection' AND missing_since IS NULL
         GROUP BY era_id
    ");
    $stmt->execute([$artistId]);

    return array_column($stmt->fetchAll(), 'n', 'era_id');
}

/** How many records each artist has on the shelf, keyed by artist id. */
function artist_record_counts(bool $visibleOnly = false): array
{
    $visible = $visibleOnly ? 'AND is_visible = 1' : '';

    return array_column(db()->query("
        SELECT artist_id, COUNT(*) AS n FROM items
         WHERE source = 'collection' $visible AND missing_since IS NULL AND artist_id IS NOT NULL
         GROUP BY artist_id
    ")->fetchAll(), 'n', 'artist_id');
}

/** Saves an era-filing rule; the same Discogs id can only file into one era. */
function save_era_rule(int $eraId, string $kind, int $discogsId, int $rank): void
{
    db()->prepare('
        INSERT INTO era_rules (era_id, kind, discogs_id, rank) VALUES (?, ?, ?, ?)
        ON CONFLICT(kind, discogs_id) DO UPDATE SET era_id = excluded.era_id
    ')->execute([$eraId, $kind, $discogsId, $rank]);
}
