<?php
require 'config.php';

echo "<h2>Aktualizácia databázy pre Zákaznícky panel</h2>";

$sql_favorites = "CREATE TABLE IF NOT EXISTS `favorites` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `establishment_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_favorite` (`user_id`, `establishment_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`establishment_id`) REFERENCES `establishments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_favorites) === TRUE) {
    echo "Tabuľka 'favorites' bola úspešne vytvorená (alebo už existuje).<br>";
} else {
    echo "Chyba pri vytváraní tabuľky 'favorites': " . $conn->error . "<br>";
}

$sql_reviews = "CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `establishment_id` INT NOT NULL,
    `booking_id` INT NULL,
    `rating` INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    `review_text` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`establishment_id`) REFERENCES `establishments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_reviews) === TRUE) {
    echo "Tabuľka 'reviews' bola úspešne vytvorená (alebo už existuje).<br>";
} else {
    echo "Chyba pri vytváraní tabuľky 'reviews': " . $conn->error . "<br>";
}

echo "<br><b>Aktualizácia hotová.</b>";
$conn->close();
?>
