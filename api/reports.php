<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $listingId = (int)($_POST['listing_id'] ?? 0);
    $reason = $_POST['reason'] ?? '';
    $description = trim($_POST['description'] ?? '');
    
    $validReasons = ['fake_listing', 'spam', 'wrong_info', 'offensive', 'suspicious_user', 'other'];
    
    if ($listingId <= 0 || !in_array($reason, $validReasons)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    $listing = fetch("SELECT * FROM listings WHERE id = ?", [$listingId]);
    if (!$listing) {
        echo json_encode(['success' => false, 'message' => 'Listing not found']);
        exit;
    }
    
    $reportedUserId = $listing['user_id'] != $_SESSION['user_id'] ? $listing['user_id'] : null;
    
    insert('reports', [
        'reporter_id' => $_SESSION['user_id'],
        'listing_id' => $listingId,
        'reported_user_id' => $reportedUserId,
        'reason' => $reason,
        'description' => $description ?: null,
        'status' => 'pending',
    ]);
    
    logActivity($_SESSION['user_id'], 'report_created', 'Reported listing: ' . $listing['title']);
    
    echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
