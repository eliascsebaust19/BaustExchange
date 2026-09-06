<?php
require_once __DIR__ . '/config.php';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // First try connecting directly to the database (works on shared hosts
    // where the DB is pre-created and the user cannot CREATE DATABASE).
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
} catch (PDOException $e) {
    // Database doesn't exist yet - try to create it (local/dev hosts only)
    try {
        $tempPdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD, $options);
        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    } catch (PDOException $e2) {
        error_log("Database connection failed: " . $e2->getMessage());
        die("A database error occurred. Please try again later.");
    }
}

// Auto-create tables if they don't exist
initializeDatabase($pdo);

function initializeDatabase($pdo) {
    try {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `level_term` VARCHAR(50) DEFAULT NULL AFTER `student_id`");
    } catch (PDOException $e) {}
    
    // Add student_level column to users (senior/junior)
    try {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `student_level` ENUM('senior','junior') DEFAULT NULL AFTER `role`");
    } catch (PDOException $e) {}
    
    // Add rent-specific columns to listings
    try {
        $pdo->exec("ALTER TABLE `listings` ADD COLUMN `rent_price` DECIMAL(10,2) DEFAULT NULL AFTER `price`");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE `listings` ADD COLUMN `rent_period` ENUM('hour','day','week','month','semester') DEFAULT NULL AFTER `rent_price`");
    } catch (PDOException $e) {}

    // Update transaction_type ENUM to support new types (buy, share, rent)
    try {
        $pdo->exec("ALTER TABLE `listings` MODIFY COLUMN `transaction_type` ENUM('sell','buy','share','exchange','rent') NOT NULL DEFAULT 'sell'");
    } catch (PDOException $e) {}
    
    // Update listings status ENUM
    try {
        $pdo->exec("ALTER TABLE `listings` MODIFY COLUMN `status` ENUM('pending','active','sold','exchanged','shared','rented','rejected','removed') NOT NULL DEFAULT 'pending'");
    } catch (PDOException $e) {}

    // Create rentals table if it doesn't exist
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `rentals` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `listing_id` INT UNSIGNED NOT NULL,
            `renter_id` INT UNSIGNED NOT NULL,
            `owner_id` INT UNSIGNED NOT NULL,
            `rent_price` DECIMAL(10,2) NOT NULL,
            `rent_period` ENUM('hour','day','week','month','semester') NOT NULL DEFAULT 'day',
            `start_date` DATE DEFAULT NULL,
            `end_date` DATE DEFAULT NULL,
            `message` TEXT DEFAULT NULL,
            `status` ENUM('pending','active','completed','cancelled','rejected','returned') NOT NULL DEFAULT 'pending',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`renter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_status` (`status`),
            INDEX `idx_renter` (`renter_id`),
            INDEX `idx_owner` (`owner_id`),
            INDEX `idx_listing` (`listing_id`)
        ) ENGINE=InnoDB");
    } catch (PDOException $e) {}

    // Create shares table if it doesn't exist
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `shares` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `listing_id` INT UNSIGNED NOT NULL,
            `requester_id` INT UNSIGNED NOT NULL,
            `owner_id` INT UNSIGNED NOT NULL,
            `message` TEXT DEFAULT NULL,
            `duration` VARCHAR(100) DEFAULT NULL,
            `status` ENUM('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_status` (`status`),
            INDEX `idx_requester` (`requester_id`),
            INDEX `idx_owner` (`owner_id`),
            INDEX `idx_listing` (`listing_id`)
        ) ENGINE=InnoDB");
    } catch (PDOException $e) {}

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) >= 11) return;
    
    $sql = file_get_contents(__DIR__ . '/../database/baust_exchange.sql');
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE `baust_exchange`.*?;/i', '', $sql);
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    error_log("Table creation error: " . $e->getMessage());
                }
            }
        }
    }
}

// Helper functions
function query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetch($sql, $params = []) {
    return query($sql, $params)->fetch();
}

function fetchAll($sql, $params = []) {
    return query($sql, $params)->fetchAll();
}

function insert($table, $data) {
    global $pdo;
    $columns = implode(', ', array_map(fn($col) => "`{$col}`", array_keys($data)));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    return $pdo->lastInsertId();
}

function update($table, $data, $where, $whereParams = []) {
    global $pdo;
    $set = implode(', ', array_map(fn($col) => "`{$col}` = ?", array_keys($data)));
    $sql = "UPDATE `{$table}` SET {$set} WHERE {$where}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge(array_values($data), $whereParams));
    return $stmt->rowCount();
}

function delete($table, $where, $params = []) {
    global $pdo;
    $sql = "DELETE FROM {$table} WHERE {$where}";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function count_rows($table, $where = "1", $params = []) {
    $result = fetch("SELECT COUNT(*) as count FROM {$table} WHERE {$where}", $params);
    return $result['count'];
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isTeacher() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'teacher';
}

function isStudent() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
}

function isSenior() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student'
        && isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'senior';
}

function isJunior() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student'
        && isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'junior';
}

function userRoleLabel($role, $studentLevel = null) {
    if ($role === 'teacher') return 'Teacher';
    if ($role === 'admin') return 'Admin';
    if ($role === 'student') {
        if ($studentLevel === 'senior') return 'Senior Student';
        if ($studentLevel === 'junior') return 'Junior Student';
        return 'Student';
    }
    return 'Student';
}

function isBlocked() {
    return isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'blocked';
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

function getProfileImageUrl($image = null) {
    if (empty($image)) {
        return '/BaustExchange/assets/images/default-avatar.png';
    }
    if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
        return $image;
    }
    if (str_starts_with($image, '/')) {
        return $image;
    }
    return '/BaustExchange/uploads/profiles/' . $image;
}

function canEditListing($listing) {
    if (isAdmin()) return true;
    return $listing['user_id'] == $_SESSION['user_id'];
}

function canDeleteListing($listing) {
    if (isAdmin()) return true;
    return $listing['user_id'] == $_SESSION['user_id'];
}

function createNotification($userId, $type, $message, $referenceId = null, $referenceType = null) {
    insert('notifications', [
        'user_id' => $userId,
        'type' => $type,
        'message' => $message,
        'reference_id' => $referenceId,
        'reference_type' => $referenceType,
    ]);
}

function getUnreadNotificationCount($userId) {
    return count_rows('notifications', 'user_id = ? AND is_read = 0', [$userId]);
}

function getUnreadMessageCount($userId) {
    return count_rows('messages', 'receiver_id = ? AND is_read = 0', [$userId]);
}

function logActivity($userId, $action, $description) {
    insert('activities', [
        'user_id' => $userId,
        'action' => $action,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    ]);
}

function redirect($url) {
    header("Location: {$url}");
    exit;
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
    } else {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}

function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'bg-warning text-dark',
        'active' => 'bg-success',
        'sold' => 'bg-info',
        'exchanged' => 'bg-primary',
        'shared' => 'bg-success',
        'rented' => 'bg-warning text-dark',
        'given' => 'bg-secondary',
        'rejected' => 'bg-danger',
        'removed' => 'bg-dark',
        'accepted' => 'bg-success',
        'cancelled' => 'bg-secondary',
        'completed' => 'bg-info',
        'blocked' => 'bg-danger',
        'reviewed' => 'bg-info',
        'resolved' => 'bg-success',
        'dismissed' => 'bg-secondary',
        'fulfilled' => 'bg-success',
        'closed' => 'bg-secondary',
        'returned' => 'bg-info',
        'approved' => 'bg-success',
    ];
    return $classes[$status] ?? 'bg-secondary';
}

function formatPrice($price, $transactionType, $rentPrice = null, $rentPeriod = null) {
    if ($transactionType === 'share') return 'Free (Share)';
    if ($transactionType === 'exchange') return 'Exchange';
    if ($transactionType === 'buy') {
        if ($price > 0) return '৳' . number_format($price);
        return 'Open to buy';
    }
    if ($transactionType === 'rent') {
        if ($rentPrice > 0) {
            $period = $rentPeriod ? ' / ' . ucfirst($rentPeriod) : '';
            return '৳' . number_format($rentPrice) . $period;
        }
        if ($price > 0) return '৳' . number_format($price) . ' (Rent)';
        return 'Rent';
    }
    if ($price > 0) return '৳' . number_format($price);
    return 'Free';
}

function timeAgo($datetime) {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
