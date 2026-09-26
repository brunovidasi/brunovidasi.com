<?php

/**
 * Two-letter region codes for the list tables, where a country name is too wide
 * for its column. ISO 3166 where there is a code, "UK" rather than "GB" as
 * Discogs and the sleeves say, and EU, WW and AS for the groups.
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

/** Groups Discogs names without spelling out the countries in them. */
const REGION_GROUPS = ['australasia'];

function region_key(string $name): string
{
    return trim(preg_replace('/\s+/', ' ', str_replace('&', 'and', mb_strtolower($name))));
}

/**
 * A region as two letters. A value naming several ("UK & Europe") takes the
 * first one's code; an unknown name comes back as it was, since a wrong code
 * is worse than a long one.
 */
function region_code(string $region): string
{
    $region = trim($region);
    if ($region === '') {
        return '';
    }

    if (preg_match('/^[A-Za-z]{2}$/', $region)) {
        return strtoupper($region);
    }

    // The whole name first, so "Trinidad & Tobago" isn't read as Trinidad.
    if (isset(REGION_CODES[region_key($region)])) {
        return REGION_CODES[region_key($region)];
    }

    $first = preg_split('/\s*(?:,|&|\band\b|\/)\s*/i', $region, -1, PREG_SPLIT_NO_EMPTY)[0] ?? '';

    return REGION_CODES[region_key($first)] ?? $region;
}

/** Whether Discogs names several regions for a pressing ("UK & Europe"). */
function region_is_multiple(string $region): bool
{
    $key = region_key($region);

    if (in_array($key, REGION_GROUPS, true)) {
        return true;
    }

    // "Trinidad & Tobago" is one country, not two.
    return !isset(REGION_CODES[$key]) && preg_match('/,|&|\band\b|\//i', $region) === 1;
}

/**
 * The region a list shows: the one set on the record, else Discogs' when it
 * names a single place, else "--" so the gap is visible.
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
