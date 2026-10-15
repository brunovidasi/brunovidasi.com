<?php

/**
 * Every field a copy can have, as the site knows it (the keys games.json had),
 * in one list: the import, the admin's edit form, its validation and the JSON
 * the site is handed all read from here, so no field can be missed.
 *
 *   label    what the admin calls it
 *   group    which part of the edit form it is in (FIELD_GROUPS)
 *   type     text | textarea | select | bool | int | color | idlist | list | json | cover | colors
 *            bool: absent, true or false (a few fields say false on purpose: "platinum": false)
 *            idlist: one platform id, or a list of them for a copy with several games in it
 *            list: a list of words; json: anything else, edited as JSON
 *   col      the games column it lives in; without one it is kept in extra_json
 *   always   handed to the site even when empty (as null), as every copy in games.json had it
 *   options  for a select: value => label, or the name of a function that returns them
 *   help     a line under the field
 *   no       a bool whose No says something ("checked, it is not"): the form offers Yes, No and
 *            neither; any other bool is a switch, on or left out
 *   sync     the platforms whose sync can write it
 *   private  only in the admin: never in what the site is handed (public_game())
 */

const FIELD_GROUPS = [
    'shelf'     => 'On the shelf',
    'basics'    => 'The game',
    'copy'      => 'This copy',
    'notes'     => 'Notes',
    'links'     => 'Platform links',
    'bundle'    => 'Bundle',
    'cover'     => 'Cover',
    'case'      => 'Case & spine',
    'cartridge' => 'Cartridge',
];

const FIELDS = [
    // ---- the game (and how it stands on the shelf, and the notes) ----
    'title'      => ['label' => 'Title', 'group' => 'basics', 'type' => 'text', 'col' => 'title', 'always' => true, 'required' => true, 'sync' => ['steam', 'nintendo', 'ea', 'rockstar']],
    'listedAs'   => ['label' => 'Listed as', 'group' => 'shelf', 'type' => 'text', 'col' => 'listed_as', 'help' => 'A shorter name the list and the spine use ("Avatar").'],
    'sortAs'     => ['label' => 'Sort as', 'group' => 'shelf', 'type' => 'text', 'col' => 'sort_as', 'help' => 'Files the copy under another name: LEGO Rock Band with the Rock Band games.'],
    'edition'    => ['label' => 'Edition', 'group' => 'basics', 'type' => 'text', 'col' => 'edition'],
    'console'    => ['label' => 'Console', 'group' => 'basics', 'type' => 'select', 'col' => 'console', 'always' => true, 'required' => true, 'options' => 'console_options', 'sync' => ['steam', 'nintendo', 'ea', 'rockstar']],
    'region'     => ['label' => 'Region', 'group' => 'basics', 'type' => 'select', 'col' => 'region', 'always' => true, 'options' => 'region_options', 'sync' => ['steam', 'nintendo', 'ea', 'rockstar']],
    'released'   => ['label' => 'Released', 'group' => 'basics', 'type' => 'text', 'col' => 'released', 'always' => true, 'help' => 'YYYY-MM-DD, or YYYY-MM, or YYYY.', 'sync' => ['steam']],
    'dateSource' => ['label' => 'Release date from', 'group' => 'basics', 'type' => 'select', 'col' => 'date_source', 'always' => true, 'options' => ['manual' => 'Typed in', 'steam' => 'Steam', 'libretro-database' => 'libretro database'], 'sync' => ['steam', 'nintendo']],
    'dateNote'   => ['label' => 'Date note', 'group' => 'basics', 'type' => 'text', 'private' => true, 'help' => 'Where the release date comes from. Only in the admin.'],
    'shelfDate'  => ['label' => 'Shelf date', 'group' => 'shelf', 'type' => 'text', 'help' => 'Files the copy under another date among its namesakes: The Sims Deluxe Edition beside The Sims.'],
    'publisher'  => ['label' => 'Publisher', 'group' => 'basics', 'type' => 'text', 'col' => 'publisher', 'always' => true, 'sync' => ['steam', 'ea', 'rockstar']],
    'developer'  => ['label' => 'Developer', 'group' => 'basics', 'type' => 'text', 'col' => 'developer', 'always' => true, 'sync' => ['steam', 'ea']],
    'genre'      => ['label' => 'Genre', 'group' => 'basics', 'type' => 'text', 'col' => 'genre', 'always' => true, 'sync' => ['steam', 'ea']],
    'series'     => ['label' => 'Series', 'group' => 'basics', 'type' => 'text', 'col' => 'series', 'help' => 'Puts the copy in The Sims collection when it is one of its series.', 'sync' => ['ea']],
    'pack'       => ['label' => 'Pack', 'group' => 'basics', 'type' => 'select', 'col' => 'pack', 'options' => 'pack_options', 'sync' => ['ea']],
    'packCode'   => ['label' => 'Pack code', 'group' => 'basics', 'type' => 'text', 'col' => 'pack_code', 'help' => 'EP08, SP12…'],
    'note'       => ['label' => 'Note', 'group' => 'notes', 'type' => 'textarea', 'col' => 'note', 'help' => 'Shown in the game\'s details.'],
    'check'      => ['label' => 'Still to check', 'group' => 'notes', 'type' => 'textarea', 'col' => 'check_note', 'private' => true, 'help' => 'A reminder to yourself. The dashboard lists the copies that have one (hidden ones aside). Only in the admin.'],
    'privateNote' => ['label' => 'Private note', 'group' => 'notes', 'type' => 'textarea', 'private' => true, 'help' => 'Anything about the copy for you alone. Never sent to the site.'],

    // ---- this copy ----
    'format'       => ['label' => 'Format', 'group' => 'copy', 'type' => 'select', 'col' => 'format', 'always' => true, 'options' => 'format_options', 'sync' => ['steam', 'nintendo', 'ea', 'rockstar']],
    'launcher'     => ['label' => 'Launcher', 'group' => 'copy', 'type' => 'select', 'col' => 'launcher', 'options' => 'launcher_options', 'help' => 'Where a PC copy activates.', 'sync' => ['steam', 'ea', 'rockstar']],
    'alsoDigital'  => ['label' => 'Also in a launcher', 'group' => 'copy', 'type' => 'bool', 'col' => 'also_digital', 'help' => 'A disc whose code was redeemed in Steam or the EA app.'],
    'mac'          => ['label' => 'Plays on Mac', 'group' => 'copy', 'type' => 'bool', 'col' => 'mac', 'sync' => ['steam']],
    'platinum'     => ['label' => 'Platinum / Essentials', 'group' => 'copy', 'type' => 'bool', 'no' => true, 'col' => 'platinum', 'help' => 'A budget re-release. No means "checked, it is not".'],
    'fav'          => ['label' => 'Favourite', 'group' => 'shelf', 'type' => 'bool', 'col' => 'fav', 'help' => 'Marked ★ Favourite in its details on the site. It doesn\'t turn it to face out: that is Faces out.'],
    'faceOut'      => ['label' => 'Faces out', 'group' => 'shelf', 'type' => 'bool', 'no' => true, 'help' => 'Faces out on the shelf in "One cover per console". No stands it on its side even when nothing else of its console faces out.'],
    'hidden'       => ['label' => 'Hidden from the site', 'group' => 'shelf', 'type' => 'bool', 'col' => 'hidden', 'help' => 'Keeps a synced game off the site. Delete it instead and the next sync brings it back.'],
    'reproduction' => ['label' => 'Reproduction', 'group' => 'copy', 'type' => 'bool', 'private' => true, 'help' => 'Not an official release. Only in the admin: the site never says so.'],
    'steelbook'    => ['label' => 'SteelBook', 'group' => 'copy', 'type' => 'bool'],
    'kinect'       => ['label' => 'Kinect', 'group' => 'copy', 'type' => 'bool'],
    'alsoOn'       => ['label' => 'Also plays on', 'group' => 'copy', 'type' => 'list', 'help' => 'Console ids, comma separated: a copy that plays on two consoles stands under each.'],
    'sameAs'       => ['label' => 'Same game as', 'group' => 'copy', 'type' => 'json', 'help' => 'Another title this one is ("The Last of Us"), or false.'],
    'model'        => ['label' => 'Model', 'group' => 'copy', 'type' => 'text', 'help' => 'A drawn model of a special box: sims3-ce, medieval-ce.'],
    'extras'       => ['label' => 'Extras in the box', 'group' => 'copy', 'type' => 'list', 'help' => 'mic, guitargrip, pokewalker…'],

    // ---- platform links ----
    'steamAppId'      => ['label' => 'Steam app id', 'group' => 'links', 'type' => 'int', 'col' => 'steam_app_id', 'platform' => 'steam', 'sync' => ['steam']],
    'psnTitleId'      => ['label' => 'PSN title id', 'group' => 'links', 'type' => 'idlist', 'col' => 'psn_title_id', 'platform' => 'psn', 'help' => 'CUSA…, PPSA…: its play history. Several, comma separated, for a copy with several games in it.', 'sync' => ['psn']],
    'psnTrophySet'    => ['label' => 'PSN trophy set', 'group' => 'links', 'type' => 'idlist', 'col' => 'psn_trophy_set', 'platform' => 'psn', 'help' => 'NPWR…: its trophies.', 'sync' => ['psn']],
    'nintendoTitleId' => ['label' => 'Nintendo title id', 'group' => 'links', 'type' => 'idlist', 'col' => 'nintendo_title_id', 'platform' => 'nintendo', 'sync' => ['nintendo']],
    'xboxTitleId'     => ['label' => 'Xbox title id', 'group' => 'links', 'type' => 'idlist', 'col' => 'xbox_title_id', 'platform' => 'xbox', 'sync' => ['xbox']],
    'eaSlug'          => ['label' => 'EA app game', 'group' => 'links', 'type' => 'text', 'col' => 'ea_slug', 'platform' => 'ea', 'sync' => ['ea']],
    'rockstarTitle'   => ['label' => 'Rockstar game', 'group' => 'links', 'type' => 'text', 'col' => 'rockstar_title', 'platform' => 'rockstar', 'help' => 'Rockstar\'s short name: gtav, rdr2, lan…', 'sync' => ['rockstar']],

    // ---- bundle ----
    'contains' => ['label' => 'Packs in it', 'group' => 'bundle', 'type' => 'list', 'col' => 'contains_json', 'help' => 'The ids of the copies that came in this bundle: they stand inside it.'],

    // ---- cover ----
    'cover'       => ['label' => 'Cover', 'group' => 'cover', 'type' => 'cover', 'col' => 'cover_json', 'always' => true, 'sync' => ['steam']],
    'colors'      => ['label' => 'Spine colours', 'group' => 'cover', 'type' => 'colors', 'col' => 'colors_json', 'always' => true, 'help' => 'The three colours that cover most of the cover; worked out from it.', 'sync' => ['steam']],
    'coverMatch'  => ['label' => 'Cover match', 'group' => 'cover', 'type' => 'text', 'help' => 'Steers Find cover: a libretro file name, a PS3 or PS2 serial, or a wiki page.'],
    'wikiCover'   => ['label' => 'Wikipedia cover', 'group' => 'cover', 'type' => 'bool', 'no' => true, 'help' => 'No stops Find cover offering Wikipedia\'s picture.'],
    'tgdbId'      => ['label' => 'TheGamesDB id', 'group' => 'cover', 'type' => 'int'],
    'launchboxId' => ['label' => 'LaunchBox id', 'group' => 'cover', 'type' => 'int'],
    'cardPhoto'   => ['label' => 'Card photo', 'group' => 'cover', 'type' => 'bool', 'no' => true, 'help' => 'No draws the card\'s label from the box art.'],

    // ---- case & spine ----
    'caseStyle'  => ['label' => 'Case style', 'group' => 'case', 'type' => 'select', 'options' => 'console_options', 'help' => 'Drawn in another console\'s case.'],
    'caseColor'  => ['label' => 'Case colour', 'group' => 'case', 'type' => 'color'],
    'spineColor' => ['label' => 'Spine colour', 'group' => 'case', 'type' => 'color'],
    'bigCase'    => ['label' => 'Big case', 'group' => 'case', 'type' => 'bool', 'help' => 'The thick PC cases of the early 2000s.'],
    'clearCase'  => ['label' => 'Clear case', 'group' => 'case', 'type' => 'bool'],
    'jewelCase'  => ['label' => 'Jewel case', 'group' => 'case', 'type' => 'bool', 'help' => 'A CD jewel case, like a music CD\'s: the PC games of the 90s.'],
    'topLabel'   => ['label' => 'Top label', 'group' => 'case', 'type' => 'bool', 'no' => true],
    'spine'      => ['label' => 'Spine', 'group' => 'case', 'type' => 'json', 'help' => 'How the spine is printed: {"style": "ea-pcgame", "logo": "logos/…", "title", "sub", "top", "foot": […], "color"…}'],
    'box'        => ['label' => 'Box size', 'group' => 'case', 'type' => 'json', 'help' => 'A box\'s own size in mm: {"w": 136, "h": 197, "d": 66}.'],

    // ---- cartridge ----
    'cartColor'   => ['label' => 'Cartridge colour', 'group' => 'cartridge', 'type' => 'color'],
    'cartBattery' => ['label' => 'Battery', 'group' => 'cartridge', 'type' => 'bool'],
    'cartMould'   => ['label' => 'Mould text', 'group' => 'cartridge', 'type' => 'text'],
    'cartBack'    => ['label' => 'Back', 'group' => 'cartridge', 'type' => 'text', 'help' => 'pat-pend, bare…'],
    'cartScrew'   => ['label' => 'Screw', 'group' => 'cartridge', 'type' => 'text'],
    'cartGlitter' => ['label' => 'Glitter', 'group' => 'cartridge', 'type' => 'bool'],
    'cartLabel'   => ['label' => 'Label box', 'group' => 'cartridge', 'type' => 'json', 'help' => 'Where the sticker is on the label photo: [x, y, w, h].'],
];

/** The keys of FIELDS that live in a column, as key => column. */
function field_columns(): array
{
    static $cols = null;

    return $cols ??= array_filter(array_map(fn ($f) => $f['col'] ?? null, FIELDS));
}

function field(string $key): ?array
{
    return FIELDS[$key] ?? null;
}

function field_options(string $key): array
{
    $options = FIELDS[$key]['options'] ?? [];

    return is_string($options) ? $options() : $options;
}

/* ---------- what the selects offer ---------- */

/** The consoles, in the site's order (js/consoles.js). */
const CONSOLES = [
    'ps5' => 'PlayStation 5', 'ps4' => 'PlayStation 4', 'ps3' => 'PlayStation 3', 'ps2' => 'PlayStation 2', 'ps1' => 'PlayStation',
    'switch2' => 'Nintendo Switch 2', 'switch' => 'Nintendo Switch', 'wiiu' => 'Wii U', '3ds' => 'Nintendo 3DS', 'wii' => 'Wii',
    'ds' => 'Nintendo DS', 'gba' => 'Game Boy Advance', 'gbc' => 'Game Boy Color', 'snes' => 'Super Nintendo', 'gb' => 'Game Boy',
    'pc' => 'PC', 'xone' => 'Xbox One', 'x360' => 'Xbox 360', 'xbox' => 'Xbox',
];

/** Their short names, as js/consoles.js has them, but with the Xbox in front of One and 360. */
const CONSOLE_SHORT = [
    'ps5' => 'PS5', 'ps4' => 'PS4', 'ps3' => 'PS3', 'ps2' => 'PS2', 'ps1' => 'PS1',
    'switch2' => 'Switch 2', 'switch' => 'Switch', 'wiiu' => 'Wii U', '3ds' => '3DS', 'wii' => 'Wii',
    'ds' => 'DS', 'gba' => 'GBA', 'gbc' => 'GBC', 'snes' => 'SNES', 'gb' => 'GB',
    'pc' => 'PC', 'xone' => 'Xbox One', 'x360' => 'Xbox 360', 'xbox' => 'Xbox',
];

const CONSOLE_FAMILIES = [
    'playstation' => ['ps5', 'ps4', 'ps3', 'ps2', 'ps1'],
    'nintendo'    => ['switch2', 'switch', 'wiiu', '3ds', 'wii', 'ds', 'gba', 'gbc', 'snes', 'gb'],
    'pc'          => ['pc'],
    'xbox'        => ['xone', 'x360', 'xbox'],
];

const REGIONS = [
    'AUS' => 'AUS — Australia', 'EUR' => 'EUR — Europe', 'HOL' => 'HOL — Netherlands', 'UK' => 'UK — United Kingdom',
    'PAL' => 'PAL — Europe or Australia', 'USA' => 'USA — United States', 'NTSC-U' => 'NTSC-U — the Americas',
    'JPN' => 'JPN — Japan', 'NTSC-J' => 'NTSC-J — Japan',
];

const FORMATS = ['boxed' => 'Boxed', 'cartridge-only' => 'Cartridge only', 'digital' => 'Digital copy', 'code-in-box' => 'Code in a box'];

const LAUNCHERS = [
    'steam' => 'Steam', 'ea' => 'EA app', 'origin' => 'Origin', 'rockstar' => 'Rockstar Games Launcher', 'battlenet' => 'Battle.net',
    'gog' => 'GOG Galaxy', 'ubisoft' => 'Ubisoft Connect', 'none' => 'DRM-free disc',
];

const PACKS = ['Base game', 'Expansion Pack', 'Game Pack', 'Stuff Pack', 'Kit', 'Collection', 'World', 'Parts Pack', 'Extra'];

function console_options(): array { return CONSOLES; }
function region_options(): array { return REGIONS; }
function format_options(): array { return FORMATS; }
function launcher_options(): array { return LAUNCHERS; }
function pack_options(): array { return array_combine(PACKS, PACKS); }

function console_name(?string $id): string
{
    return CONSOLES[$id] ?? (string) $id;
}

function console_short(?string $id): string
{
    return CONSOLE_SHORT[$id] ?? console_name($id);
}

function console_family(string $id): string
{
    foreach (CONSOLE_FAMILIES as $family => $ids) {
        if (in_array($id, $ids, true)) {
            return $family;
        }
    }

    return 'pc';
}
