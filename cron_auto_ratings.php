<?php
require_once 'config.php';
require_once 'api/auto_ratings_helper.php';
require_once 'includes/occasion_automation_helper.php';

header('Content-Type: application/json');

process48HourAutoRatings($conn);
sendReviewRequestEmails($conn);
sendWinbackEmails($conn);
processOccasionAutomation($conn);

echo json_encode([
    'success' => true,
    'message' => '48-hour auto-ratings, review-request, win-back and occasion automation emails processed successfully.',
    'timestamp' => date('Y-m-d H:i:s')
]);
