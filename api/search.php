<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['results' => []]);
    exit;
}

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$searchTerm = "%{$query}%";

// Search listings
$listings = fetchAll(
    "SELECT l.id, l.title, c.name as category_name,
     (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as image
     FROM listings l 
     JOIN categories c ON l.category_id = c.id 
     WHERE l.status = 'active' AND (l.title LIKE ? OR l.description LIKE ?)
     ORDER BY l.created_at DESC LIMIT 10",
    [$searchTerm, $searchTerm]
);

// Search users (limited info)
$users = fetchAll(
    "SELECT id, name, role, student_level, profile_image 
     FROM users 
     WHERE name LIKE ? AND status = 'active'
     LIMIT 5",
    [$searchTerm]
);

echo json_encode([
    'listings' => $listings,
    'users' => $users,
]);
