<?php

/**
 * First-run content for a brand-new database: the four artist pages and their
 * eras, with Lady Gaga's master ids carried over from the hand-written page
 * this app replaced. Everything is editable in the admin afterwards.
 */
function seed_artists_and_eras(PDO $db): void
{
    $artists = [
        [
            'slug' => 'lady-gaga', 'name' => 'Lady Gaga', 'match' => ['Lady Gaga'],
            'tagline' => 'Every CD and vinyl in the collection, era by era.',
            'accent' => '#C99A2E',
            'eras' => [
                ['the-fame', 'The Fame', '2008–2009', 'Just Dance · Poker Face · Paparazzi', [11126, 77385, 77387, 77389, 104888, 146544, 77394, 397264], [1482291, 1904079]],
                ['the-fame-monster', 'The Fame Monster', '2009–2010', 'Bad Romance · Telephone · Alejandro', [201057, 200007, 234339, 256023, 245419], [2651622]],
                ['born-this-way', 'Born This Way', '2011', 'Born This Way · Judas · The Edge of Glory', [338175, 316302, 334051, 342489, 371513, 388411, 387281, 815330], [3061184]],
                ['artpop', 'ARTPOP', '2013–2014', 'Applause · Do What U Want · G.U.Y.', [616094, 585303], []],
                ['cheek-to-cheek', 'Cheek to Cheek', '2014', 'With Tony Bennett', [735298, 819592], []],
                ['joanne', 'Joanne', '2016–2017', 'Perfect Illusion · Million Reasons · The Cure', [1077237, 1167109], []],
                ['a-star-is-born', 'A Star Is Born', '2018', 'Shallow · Always Remember Us This Way', [1433566], []],
                ['chromatica', 'Chromatica', '2020–2022', 'Stupid Love · Rain On Me · 911', [1746431, 1691089, 1742623, 2287330], []],
                ['love-for-sale', 'Love For Sale', '2021', 'With Tony Bennett', [2321728], []],
                ['top-gun-wednesday', 'Top Gun & Wednesday', '2022–2023', 'Hold My Hand · Bloody Mary', [2623907, 2650322, 3041759], []],
                ['harlequin', 'Harlequin', '2024', 'Harlequin · Joker: Folie à Deux', [3609260, 3615808], []],
                ['mayhem', 'Mayhem', '2025', 'Abracadabra · Die With A Smile', [3772997, 3572399], []],
            ],
        ],
        [
            'slug' => 'beyonce', 'name' => 'Beyoncé', 'match' => ['Beyoncé', 'Beyonce', 'Destiny\'s Child'],
            'tagline' => 'Every disc in the collection, era by era.',
            'accent' => '#A67C2E',
            'eras' => [
                ['dangerously-in-love', 'Dangerously in Love', '2003–2004', 'Crazy in Love · Baby Boy', [], []],
                ['bday', "B'Day", '2006–2007', 'Déjà Vu · Irreplaceable', [], []],
                ['sasha-fierce', 'I Am… Sasha Fierce', '2008–2010', 'Single Ladies · Halo', [], []],
                ['4', '4', '2011–2012', 'Run the World · Love On Top', [], []],
                ['beyonce', 'BEYONCÉ', '2013–2015', 'Drunk in Love · Partition', [], []],
                ['lemonade', 'Lemonade', '2016–2017', 'Formation · Sorry', [], []],
                ['everything-is-love', 'Everything Is Love & The Gift', '2018–2019', 'The Carters · The Lion King', [], []],
                ['renaissance', 'Renaissance', '2022–2023', 'Break My Soul · Cuff It', [], []],
                ['cowboy-carter', 'Cowboy Carter', '2024–2025', 'Texas Hold \'Em · 16 Carriages', [], []],
            ],
        ],
        [
            'slug' => 'anitta', 'name' => 'Anitta', 'match' => ['Anitta'],
            'tagline' => 'Every disc in the collection, era by era.',
            'accent' => '#A63A2C',
            'eras' => [
                ['anitta', 'Anitta', '2013', 'Show das Poderosas', [], []],
                ['ritmo-perfeito', 'Ritmo Perfeito', '2014', 'Na Batida · Blá Blá Blá', [], []],
                ['bang', 'Bang', '2015–2016', 'Bang · Deixa Ele Sofrer', [], []],
                ['kisses', 'Kisses', '2019', 'Medicina · Banana', [], []],
                ['versions-of-me', 'Versions of Me', '2021–2022', 'Envolver · Boys Don\'t Cry', [], []],
                ['funk-generation', 'Funk Generation', '2023–2024', 'Funk Rave · Mil Veces', [], []],
            ],
        ],
        [
            'slug' => 'rbd', 'name' => 'RBD', 'match' => ['RBD', 'Rebelde'],
            'tagline' => 'Every disc in the collection, era by era.',
            'accent' => '#1F5C52',
            'eras' => [
                ['rebelde', 'Rebelde', '2004–2005', 'Rebelde · Sólo Quédate en Silencio', [], []],
                ['nuestro-amor', 'Nuestro Amor', '2005–2006', 'Nuestro Amor · Aún Hay Algo', [], []],
                ['celestial', 'Celestial', '2006–2007', 'Ser o Parecer · Bésame Sin Miedo', [], []],
                ['empezar-desde-cero', 'Empezar Desde Cero', '2007–2008', 'Inalcanzable · Y No Puedo Olvidarte', [], []],
                ['para-olvidarte-de-mi', 'Para Olvidarte de Mí', '2009', 'Para Olvidarte de Mí', [], []],
                ['soy-rebelde-tour', 'Soy Rebelde Tour', '2023', 'The reunion', [], []],
            ],
        ],
    ];

    $insertArtist = $db->prepare('INSERT INTO artists (slug, name, match_names, tagline, accent, position) VALUES (?, ?, ?, ?, ?, ?)');
    $insertEra = $db->prepare('INSERT INTO eras (artist_id, slug, name, years, tagline, position) VALUES (?, ?, ?, ?, ?, ?)');
    $insertRule = $db->prepare('INSERT OR IGNORE INTO era_rules (era_id, kind, discogs_id, rank) VALUES (?, ?, ?, ?)');

    foreach ($artists as $artistPosition => $artist) {
        $insertArtist->execute([
            $artist['slug'],
            $artist['name'],
            json_encode($artist['match'], JSON_UNESCAPED_UNICODE),
            $artist['tagline'],
            $artist['accent'],
            $artistPosition,
        ]);
        $artistId = (int) $db->lastInsertId();

        foreach ($artist['eras'] as $eraPosition => [$slug, $name, $years, $tagline, $masters, $releases]) {
            $insertEra->execute([$artistId, $slug, $name, $years, $tagline, $eraPosition]);
            $eraId = (int) $db->lastInsertId();

            foreach ($masters as $rank => $masterId) {
                $insertRule->execute([$eraId, 'master', $masterId, $rank]);
            }
            // Releases without a master (mostly promos) sort after the masters.
            foreach ($releases as $rank => $releaseId) {
                $insertRule->execute([$eraId, 'release', $releaseId, 1000 + $rank]);
            }
        }
    }
}
