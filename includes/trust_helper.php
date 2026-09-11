<?php
/**
 * Zdieľaná logika dôveryhodnostného skóre (obojsmerné hodnotenia zákazník↔prevádzka).
 * Používa ju api/reviews.php (zobrazenie) aj api/book_appointment.php (rozhodnutie o zálohe).
 */

if (!function_exists('reviewRevealedCondition')) {
    // "Slepé" hodnotenie: viditeľné až keď existujú OBE strany pre danú rezerváciu
    // (reálne, alebo automatické 5★ po 72 h — pozri api/auto_ratings_helper.php).
    function reviewRevealedCondition($otherReviewerType) {
        return "EXISTS (SELECT 1 FROM reviews r2 WHERE r2.booking_id = r.booking_id AND r2.reviewer_type = '$otherReviewerType')";
    }
}

if (!function_exists('computeAggregateScore')) {
    // Verejné len ak existujú aspoň 3 viditeľné hodnotenia, inak "Nový/Nová" (average = null).
    function computeAggregateScore($conn, $revieweeId, $revieweeType, $otherReviewerType) {
        $revealed = reviewRevealedCondition($otherReviewerType);
        $sql = "SELECT ROUND(AVG(r.rating), 2) as avg_rating, COUNT(*) as cnt
                FROM reviews r
                WHERE r.reviewee_id = $revieweeId AND r.reviewee_type = '$revieweeType' AND $revealed";
        $row = $conn->query($sql)->fetch_assoc();
        $cnt = (int)($row['cnt'] ?? 0);
        return [
            'count'   => $cnt,
            'average' => $cnt >= 3 ? (float)$row['avg_rating'] : null,
            'is_new'  => $cnt < 3,
        ];
    }
}

if (!function_exists('getCustomerTrustTier')) {
    // 'trusted' (>=4.5, bez zálohy), 'risky' (<3.0, zvýšená záloha), 'standard' (ostatné/nový zákazník)
    function getCustomerTrustTier($conn, $customerId) {
        $stats = computeAggregateScore($conn, $customerId, 'customer', 'customer');
        $tier = 'standard';
        if (!$stats['is_new']) {
            if ($stats['average'] >= 4.5) { $tier = 'trusted'; }
            elseif ($stats['average'] < 3.0) { $tier = 'risky'; }
        }
        return ['tier' => $tier, 'average' => $stats['average'], 'count' => $stats['count']];
    }
}
