<?php
/**
 * Vernostný program: "pečiatková karta" za návštevy alebo za minutú sumu.
 * Body/návštevy sa pripočítajú vždy len raz — pri prechode rezervácie DO stavu 'completed'
 * (volajúci musí sám overiť, že predošlý stav 'completed' nebol, inak by sa počítalo viackrát).
 */

function ensureLoyaltyTables($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS loyalty_programs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        establishment_id INT NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 0,
        reward_type ENUM('visits','spend') NOT NULL DEFAULT 'visits',
        reward_threshold DECIMAL(10,2) NOT NULL DEFAULT 10,
        reward_description VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_est (establishment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS customer_loyalty_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        establishment_id INT NOT NULL,
        visits_count INT NOT NULL DEFAULT 0,
        spend_total DECIMAL(10,2) NOT NULL DEFAULT 0,
        rewards_redeemed INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_customer_est (customer_id, establishment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Počet dosiahnutých odmien (celkovo za celý čas) podľa aktuálneho nastavenia programu
function loyaltyRewardsEarned($program, $progress) {
    if (!$program || !$progress || (float)$program['reward_threshold'] <= 0) return 0;
    $value = $program['reward_type'] === 'spend' ? (float)$progress['spend_total'] : (int)$progress['visits_count'];
    return (int)floor($value / (float)$program['reward_threshold']);
}

function awardLoyaltyPoints($conn, $booking_id) {
    ensureLoyaltyTables($conn);

    $b_stmt = $conn->prepare("SELECT b.customer_id, b.establishment_id, s.price FROM bookings b LEFT JOIN services s ON s.id = b.service_id WHERE b.id = ?");
    $b_stmt->bind_param("i", $booking_id);
    $b_stmt->execute();
    $booking = $b_stmt->get_result()->fetch_assoc();
    if (!$booking || !$booking['customer_id']) return;

    $customer_id = (int)$booking['customer_id'];
    $establishment_id = (int)$booking['establishment_id'];
    $price = (float)($booking['price'] ?? 0);

    $prog_stmt = $conn->prepare("SELECT is_active FROM loyalty_programs WHERE establishment_id = ?");
    $prog_stmt->bind_param("i", $establishment_id);
    $prog_stmt->execute();
    $program = $prog_stmt->get_result()->fetch_assoc();
    if (!$program || !$program['is_active']) return;

    $ins = $conn->prepare("INSERT INTO customer_loyalty_progress (customer_id, establishment_id, visits_count, spend_total)
                            VALUES (?, ?, 1, ?)
                            ON DUPLICATE KEY UPDATE visits_count = visits_count + 1, spend_total = spend_total + VALUES(spend_total)");
    $ins->bind_param("iid", $customer_id, $establishment_id, $price);
    $ins->execute();
}
