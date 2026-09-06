<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'count':
        if (!isLoggedIn()) {
            echo json_encode(['count' => 0]);
            exit;
        }
        $count = getUnreadNotificationCount($_SESSION['user_id']);
        echo json_encode(['count' => $count]);
        break;
        
    case 'mark_read':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false]);
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            query("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$id, $_SESSION['user_id']]);
        }
        echo json_encode(['success' => true]);
        break;
        
    case 'mark_all_read':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false]);
            exit;
        }
        query("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
