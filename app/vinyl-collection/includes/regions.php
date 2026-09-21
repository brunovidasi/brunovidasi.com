<?php

/**
 * Two-letter region codes for the list tables.
 *
 * Discogs names a pressing's country in words ("Europe", "Australia & New
 * Zealand", "UK, Europe & US"), which is too wide for a column, so the lists
 * show a code and put the full name on hover. Nothing stored changes: the drawer
 * and the admin still say the whole thing.
 *
 * A pressing that Discogs lists for several regions is a different matter: the
 * list shows what you set on the record, and "--" until you have (see
 * region_for_list()).
 *
 * ISO 3166 where there is a code (with "UK", not "GB", because that is what
 * Discogs and every sleeve say), and the usual stand-ins where there isn't:
 * EU, WW for Worldwide, AS for Asia.
 */

const REGION_CODES = [
    'europe' => 'EU', 'worldwide' => 'WW', 'asia' => 'AS', 'australasia' => 'AU',
    'usa' => 'US', 'united states' => 'US', 'uk' => 'UK', 'united kingdom' => 'UK',
    'afghanistan' => 'AF', 'albania' => 'AL', 'algeria' => 'DZ', 'andorra' => 'AD', 'angola' => 'AO',
    'argentina' => 'AR', 'armenia' => 'AM', 'aruba' => 'AW', 'australia' => 'AU', 'austria' => 'AT',
    'azerbaijan' => 'AZ', 'bahamas' => 'BS', 'bahrain' => 'BH', 'bangladesh' => 'BD', 'barbados' => 'BB',
    'belarus' => 'BY', 'belgium' => 'BE', 'belize' => 'BZ', 'benin' => 'BJ', 'bermuda' => 'BM',
    'bolivia' => 'BO', 'bosnia and herzegovina' => 'BA', 'botswana' => 'BW', 'brazil' => 'BR',
    'brunei' => 'BN', 'bulgaria' => 'BG', 'cambodia' => 'KH', 'cameroon' => 'CM', 'canada' => 'CA',
    'cape verde' => 'CV', 'chile' => 'CL', 'china' => 'CN', 'colombia' => 'CO', 'costa rica' => 'CR',
    'croatia' => 'HR', 'cuba' => 'CU', 'cyprus' => 'CY', 'czech republic' => 'CZ', 'czechia' => 'CZ',
    'czechoslovakia' => 'CS', 'denmark' => 'DK', 'dominican republic' => 'DO', 'east germany' => 'DD',
    'ecuador' => 'EC', 'egypt' => 'EG', 'el salvador' => 'SV', 'estonia' => 'EE', 'ethiopia' => 'ET',
    'faroe islands' => 'FO', 'fiji' => 'FJ', 'finland' => 'FI', 'france' => 'FR', 'georgia' => 'GE',
    'germany' => 'DE', 'ghana' => 'GH', 'greece' => 'GR', 'greenland' => 'GL', 'guatemala' => 'GT',
    'honduras' => 'HN', 'hong kong' => 'HK', 'hungary' => 'HU', 'iceland' => 'IS', 'india' => 'IN',
    'indonesia' => 'ID', 'iran' => 'IR', 'iraq' => 'IQ', 'ireland' => 'IE', 'israel' => 'IL',
    'italy' => 'IT', 'jamaica' => 'JM', 'japan' => 'JP', 'jordan' => 'JO', 'kazakhstan' => 'KZ',
    'kenya' => 'KE', 'korea' => 'KR', 'south korea' => 'KR', 'kuwait' => 'KW', 'latvia' => 'LV',
    'lebanon' => 'LB', 'lithuania' => 'LT', 'luxembourg' => 'LU', 'macau' => 'MO', 'macedonia' => 'MK',
    'north macedonia' => 'MK', 'malaysia' => 'MY', 'malta' => 'MT', 'mexico' => 'MX', 'moldova' => 'MD',
    'monaco' => 'MC', 'montenegro' => 'ME', 'morocco' => 'MA', 'namibia' => 'NA', 'nepal' => 'NP',
    'netherlands' => 'NL', 'new zealand' => 'NZ', 'nicaragua' => 'NI', 'nigeria' => 'NG', 'norway' => 'NO',
    'pakistan' => 'PK', 'panama' => 'PA', 'paraguay' => 'PY', 'peru' => 'PE', 'philippines' => 'PH',
    'poland' => 'PL', 'portugal' => 'PT', 'puerto rico' => 'PR', 'qatar' => 'QA', 'romania' => 'RO',
    'russia' => 'RU', 'saudi arabia' => 'SA', 'serbia' => 'RS', 'serbia and montenegro' => 'CS',
    'singapore' => 'SG', 'slovakia' => 'SK', 'slovenia' => 'SI', 'south africa' => 'ZA', 'spain' => 'ES',
    'sri lanka' => 'LK', 'sweden' => 'SE', 'switzerland' => 'CH', 'taiwan' => 'TW', 'thailand' => 'TH',
    'trinidad and tobago' => 'TT', 'tunisia' => 'TN', 'turkey' => 'TR', 'ukraine' => 'UA', 'ussr' => 'SU',
    'united arab emirates' => 'AE', 'uruguay' => 'UY', 'venezuela' => 'VE', 'vietnam' => 'VN',
    'yugoslavia' => 'YU', 'zimbabwe' => 'ZW',
];

/**
 * A region as two letters. A value naming several ("UK & Europe", as typed into
 * a record's Region box) takes the code of the first one named. A name that
 * isn't known comes back as it was, because a wrong code is worse than a long
 * one. Discogs' own multi-region values don't come through here: see
 * region_for_list().
 */
function region_code(string $region): string
{
    $region = trim($region);
    if ($region === '') {
        return '';
    }

    // Already a code, typed by hand or by Discogs ("US", "UK").
    if (preg_match('/^[A-Za-z]{2}$/', $region)) {
        return strtoupper($region);
    }

    $key = fn (string $name): string => trim(preg_replace('/\s+/', ' ', str_replace('&', 'and', mb_strtolower($name))));

    // The whole name first, so "Trinidad & Tobago" isn't read as Trinidad.
    if (isset(REGION_CODES[$key($region)])) {
        return REGION_CODES[$key($region)];
    }

    $first = preg_split('/\s*(?:,|&|\band\b|\/)\s*/i', $region, -1, PREG_SPLIT_NO_EMPTY)[0] ?? '';

    return REGION_CODES[$key($first)] ?? $region;
}

/** Discogs' names for a group of countries that don't spell the group out. */
const REGION_GROUPS = ['australasia'];

/** Whether Discogs lists several regions for a pressing ("UK & Europe"), so it doesn't say which one you own. */
function region_is_multiple(string $region): bool
{
    $key = trim(preg_replace('/\s+/', ' ', str_replace('&', 'and', mb_strtolower($region))));

    if (in_array($key, REGION_GROUPS, true)) {
        return true;
    }

    // "Trinidad & Tobago" is one country, not two.
    return !isset(REGION_CODES[$key]) && preg_match('/,|&|\band\b|\//i', $region) === 1;
}

/**
 * The region the list shows for a record: the one you set on it, else Discogs'
 * where it names a single place. Where Discogs lists several and you haven't
 * said which one you own, "--" — so the gap is visible and there's something to
 * go and fill in.
 */
function region_for_list(string $own, string $discogs): string
{
    if (trim($own) !== '') {
        return region_code($own);
    }

    if (trim($discogs) === '') {
        return '';
    }

    return region_is_multiple($discogs) ? '--' : region_code($discogs);
}
