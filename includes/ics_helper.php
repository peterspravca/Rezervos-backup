<?php
/**
 * Zdieľané generovanie .ics súboru pre rezervácie — používa ho e-mailová príloha
 * aj priame stiahnutie z prehliadača (Apple Kalendár, Outlook desktop, Thunderbird...).
 */

if (!function_exists('ics_escape')) {
    // RFC 5545 — čiarka, bodkočiarka a spätná lomka sa musia escapovať, nie HTML entity.
    function ics_escape($text) {
        return str_replace(["\\", ",", ";", "\n"], ["\\\\", "\\,", "\\;", "\\n"], (string)$text);
    }
}

if (!function_exists('generate_ics_content')) {
    function generate_ics_content($details) {
        $start_timestamp = strtotime($details['date'] . ' ' . $details['time']);
        $end_timestamp = $start_timestamp + ((int)$details['duration'] * 60);

        $dtstart = gmdate('Ymd\THis\Z', $start_timestamp);
        $dtend = gmdate('Ymd\THis\Z', $end_timestamp);
        $now = gmdate('Ymd\THis\Z');
        $uid = ($details['uid'] ?? uniqid()) . '@rezervos.eu';

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Rezervos.eu//NONSGML v1.0//EN\r\n";
        $ics .= "METHOD:REQUEST\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:" . $uid . "\r\n";
        $ics .= "DTSTAMP:" . $now . "\r\n";
        $ics .= "DTSTART:" . $dtstart . "\r\n";
        $ics .= "DTEND:" . $dtend . "\r\n";
        $ics .= "SUMMARY:" . ics_escape($details['service_name'] . ' v ' . $details['establishment_name']) . "\r\n";
        $ics .= "DESCRIPTION:" . ics_escape('Termín u prevádzky ' . $details['establishment_name']) . "\r\n";
        $ics .= "LOCATION:" . ics_escape($details['location']) . "\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }
}
