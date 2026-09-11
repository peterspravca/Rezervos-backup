<?php
// includes/contact_filter.php

/**
 * Normalizes Slovak and Czech number words to digits to prevent bypasses.
 */
function normalize_words_to_digits($text) {
    if (empty($text)) return '';

    // Slovak & Czech word-to-digit map (accented and normalized)
    $word_map = [
        'nula' => '0',
        'jeden' => '1', 'jedna' => '1', 'jedno' => '1',
        'dva' => '2', 'dve' => '2', 'dvoch' => '2', 'dvaja' => '2',
        'tri' => '3', 'traja' => '3',
        'styri' => '4', 'štyri' => '4', 'štvri' => '4', 'štiri' => '4', 'ctyri' => '4', 'čtyři' => '4',
        'pat' => '5', 'päť' => '5', 'pet' => '5', 'pět' => '5',
        'sest' => '6', 'šesť' => '6', 'šest' => '6',
        'sedem' => '7', 'sedm' => '7',
        'osem' => '8', 'osm' => '8',
        'devat' => '9', 'deväť' => '9', 'devet' => '9', 'devět' => '9'
    ];

    $normalized = mb_strtolower($text, 'UTF-8');

    // Replace each word with its corresponding digit
    foreach ($word_map as $word => $digit) {
        // Use word boundaries to prevent replacing parts of other words
        $pattern = '/(?<![a-záäčďéíľĺňóôŕšťúýž])' . preg_quote($word, '/') . '(?![a-záäčďéíľĺňóôŕšťúýž])/u';
        $normalized = preg_replace($pattern, $digit, $normalized);
    }

    return $normalized;
}

/**
 * Checks if the text contains forbidden contact information (email, phone, or URL/link).
 * Returns true if forbidden content is found, false otherwise.
 * If found, $detected_type will contain 'email', 'phone', or 'url'.
 */
function has_offplatform_contact($text, &$detected_type = null) {
    if (empty($text)) return false;

    // 1. Check for Emails (standard and obfuscated)
    // Matches: user@domain.com, user @ domain . com, user [at] domain [dot] com, user (zavinac) domain (bodka) sk, etc.
    $email_pattern = '/[a-zA-Z0-9._%+-]+[\s\(\[\]_]*?(?:@|\[\s*at\s*\]|\(\s*at\s*\)|(?:\s+|^)at(?:\s+|$)|\[\s*zavinac\s*\]|\(\s*zavinac\s*\)|(?:\s+|^)zavinac(?:\s+|$)|\[\s*zavináč\s*\]|\(\s*zavináč\s*\)|(?:\s+|^)zavináč(?:\s+|$))[\s\(\[\]_]*?[a-zA-Z0-9.-]+[\s\(\[\]_]*?(?:\.|\bdot\b|\bbodka\b|\[\s*dot\s*\]|\(\s*dot\s*\)|\[\s*bodka\s*\]|\(\s*bodka\s*\))[\s\(\[\]_]*?[a-zA-Z]{2,4}/ui';
    
    if (preg_match($email_pattern, $text)) {
        $detected_type = 'email';
        return true;
    }

    // 2. Check for URLs / Web addresses
    // Matches: https://mojweb.sk, www.mojweb.sk, mojweb.sk, mojweb dot sk, w w w . mojweb . sk, mojweb [bodka] sk
    $url_patterns = [
        // Standard URLs starting with http, https, ftp, or www (even if spaced)
        '/(?:https?:\/\/|ftp:\/\/|www\.|w\s*w\s*w\s*[\.\,]\s*)[a-zA-Z0-9\-\.\_]+[\.\,]\s*[a-zA-Z]{2,6}/ui',
        // Domain with common TLDs (e.g. mojweb.sk, mojweb dot sk, mojweb (bodka) cz, etc.)
        '/\b[a-zA-Z0-9\-]{3,}(?:\s*[\.\,]\s*|\s*[\(\[\]_]*\s*(?:dot|bodka)\s*[\)\]_]?\s*|\s+(?:dot|bodka)\s+)(?:sk|cz|com|net|org|info|eu|hu|pl|xyz|online|top|store|biz|site|info|de|at|co|uk|ru)\b/ui'
    ];

    foreach ($url_patterns as $pattern) {
        if (preg_match($pattern, $text)) {
            $detected_type = 'url';
            return true;
        }
    }

    // 3. Check for Phone Numbers
    // First, convert any written number words to digits to prevent bypass (e.g. "nula devat...")
    $normalized_text = normalize_words_to_digits($text);

    // Slovak and Czech mobile/landline regex
    // Matches: 0905 123 456, 0 9 0 5 1 2 3 4 5 6, +421 905 123 456, 777123456, etc.
    $phone_pattern = '/(?:\+?42[01]|00\s*42[01])?[\s\.\-\/\(\)\[\]]*0?[\s\.\-\/\(\)\[\]]*[2-79](?:[\s\.\-\/\(\)\[\]]*[0-9]){8}/ui';

    if (preg_match_all($phone_pattern, $normalized_text, $matches)) {
        foreach ($matches[0] as $match) {
            $digits = preg_replace('/[^0-9]/', '', $match);
            
            // Slovak / Czech numbers have 9-10 digits (excluding 421/420 country code)
            // Let's count unique digits to avoid false positives on large numbers (like 500 000 000)
            $unique_digits = count(array_unique(str_split($digits)));
            
            if ($unique_digits >= 3) {
                $detected_type = 'phone';
                return true;
            }
        }
    }

    return false;
}
