<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'load_more':
        $userId = (int)($_GET['user_id'] ?? 0);
        $lastId = (int)($_GET['last_id'] ?? 0);
        
        if ($userId <= 0) {
            echo json_encode(['messages' => []]);
            exit;
        }
        
        $sql = "SELECT m.*, u.name as sender_name 
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))";
        $params = [$_SESSION['user_id'], $userId, $userId, $_SESSION['user_id']];
        
        if ($lastId > 0) {
            $sql .= " AND m.id < ?";
            $params[] = $lastId;
        }
        
        $sql .= " ORDER BY m.created_at DESC LIMIT 20";
        $messages = fetchAll($sql, $params);
        
        echo json_encode(['messages' => $messages]);
        break;
        
    case 'mark_read':
        $messageId = (int)($_POST['id'] ?? 0);
        if ($messageId > 0) {
            query("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?", 
                [$messageId, $_SESSION['user_id']]);
        }
        echo json_encode(['success' => true]);
        break;
        
    case 'check_new':
        $userId = (int)($_GET['user_id'] ?? 0);
        $lastId = (int)($_GET['last_id'] ?? 0);
        
        if ($userId <= 0) {
            echo json_encode(['messages' => []]);
            exit;
        }
        
        $sql = "SELECT m.*, u.name as sender_name 
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                AND m.id > ?";
        $messages = fetchAll($sql, [$_SESSION['user_id'], $userId, $userId, $_SESSION['user_id'], $lastId]);
        
        echo json_encode(['messages' => $messages]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
