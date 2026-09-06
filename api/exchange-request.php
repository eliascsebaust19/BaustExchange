<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (isBlocked()) {
    echo json_encode(['success' => false, 'message' => 'Your account is blocked']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $listingId = (int)($_POST['listing_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $offeredItem = trim($_POST['offered_item'] ?? '');
        $transactionType = $_POST['transaction_type'] ?? 'exchange';
        $duration = trim($_POST['duration'] ?? '');
        
        if ($listingId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid listing']);
            exit;
        }
        
        $listing = fetch("SELECT * FROM listings WHERE id = ? AND status = 'active'", [$listingId]);
        if (!$listing) {
            echo json_encode(['success' => false, 'message' => 'Listing not found or inactive']);
            exit;
        }
        
        if ($listing['user_id'] == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot request your own listing']);
            exit;
        }
        
        // Route based on listing transaction type
        if ($transactionType === 'rent') {
            // Check for existing pending rental request
            $existing = fetch(
                "SELECT id FROM rentals WHERE listing_id = ? AND renter_id = ? AND status IN ('pending','active')",
                [$listingId, $_SESSION['user_id']]
            );
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'You already have a pending rental request for this item']);
                exit;
            }
            
            $requestId = insert('rentals', [
                'listing_id' => $listingId,
                'renter_id' => $_SESSION['user_id'],
                'owner_id' => $listing['user_id'],
                'rent_price' => $listing['rent_price'] ?? $listing['price'] ?? 0,
                'rent_period' => $listing['rent_period'] ?? 'day',
                'message' => $message ?: null,
                'status' => 'pending',
            ]);
            
            createNotification($listing['user_id'], 'rent_request', 
                $_SESSION['user_name'] . ' wants to rent your "' . $listing['title'] . '"',
                $requestId, 'rental');
            
            logActivity($_SESSION['user_id'], 'rent_request_created', 'Sent rental request for: ' . $listing['title']);
            
            echo json_encode(['success' => true, 'message' => 'Rental request sent successfully!']);
            exit;
        }
        
        if ($transactionType === 'share') {
            // Check for existing pending share request
            $existing = fetch(
                "SELECT id FROM shares WHERE listing_id = ? AND requester_id = ? AND status IN ('pending','approved')",
                [$listingId, $_SESSION['user_id']]
            );
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'You already have a pending share request for this item']);
                exit;
            }
            
            $requestId = insert('shares', [
                'listing_id' => $listingId,
                'requester_id' => $_SESSION['user_id'],
                'owner_id' => $listing['user_id'],
                'message' => $message ?: null,
                'duration' => $duration ?: null,
                'status' => 'pending',
            ]);
            
            createNotification($listing['user_id'], 'share_request', 
                $_SESSION['user_name'] . ' wants to share your "' . $listing['title'] . '"',
                $requestId, 'share');
            
            logActivity($_SESSION['user_id'], 'share_request_created', 'Sent share request for: ' . $listing['title']);
            
            echo json_encode(['success' => true, 'message' => 'Share request sent successfully!']);
            exit;
        }
        
        // Check for existing pending request
        $existing = fetch(
            "SELECT id FROM exchange_requests WHERE listing_id = ? AND sender_id = ? AND status IN ('pending','accepted')",
            [$listingId, $_SESSION['user_id']]
        );
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'You already have a pending request for this item']);
            exit;
        }
        
        $requestId = insert('exchange_requests', [
            'listing_id' => $listingId,
            'sender_id' => $_SESSION['user_id'],
            'receiver_id' => $listing['user_id'],
            'message' => $message ?: null,
            'offered_item' => $offeredItem ?: null,
            'status' => 'pending',
        ]);
        
        // Notify listing owner
        createNotification($listing['user_id'], 'exchange_request', 
            $_SESSION['user_name'] . ' wants to exchange for your "' . $listing['title'] . '"',
            $requestId, 'request');
        
        logActivity($_SESSION['user_id'], 'request_created', 'Sent exchange request for: ' . $listing['title']);
        
        echo json_encode(['success' => true, 'message' => 'Exchange request sent successfully!']);
        break;
        
    case 'accept':
        $requestId = (int)($_POST['id'] ?? 0);
        $request = fetch("SELECT * FROM exchange_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'", 
            [$requestId, $_SESSION['user_id']]);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }
        
        update('exchange_requests', ['status' => 'accepted'], 'id = ?', [$requestId]);
        
        // Notify sender
        createNotification($request['sender_id'], 'request_accepted', 
            'Your exchange request for "' . fetch("SELECT title FROM listings WHERE id = ?", [$request['listing_id']])['title'] . '" was accepted!',
            $requestId, 'request');
        
        logActivity($_SESSION['user_id'], 'request_accepted', 'Accepted exchange request');
        
        echo json_encode(['success' => true]);
        break;
        
    case 'reject':
        $requestId = (int)($_POST['id'] ?? 0);
        $request = fetch("SELECT * FROM exchange_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'", 
            [$requestId, $_SESSION['user_id']]);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }
        
        update('exchange_requests', ['status' => 'rejected'], 'id = ?', [$requestId]);
        
        // Notify sender
        createNotification($request['sender_id'], 'request_rejected', 
            'Your exchange request was rejected.',
            $requestId, 'request');
        
        logActivity($_SESSION['user_id'], 'request_rejected', 'Rejected exchange request');
        
        echo json_encode(['success' => true]);
        break;
        
    case 'cancel':
        $requestId = (int)($_POST['id'] ?? 0);
        $request = fetch("SELECT * FROM exchange_requests WHERE id = ? AND sender_id = ? AND status = 'pending'", 
            [$requestId, $_SESSION['user_id']]);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }
        
        update('exchange_requests', ['status' => 'cancelled'], 'id = ?', [$requestId]);
        logActivity($_SESSION['user_id'], 'request_cancelled', 'Cancelled exchange request');
        
        echo json_encode(['success' => true]);
        break;
        
    case 'complete':
        $requestId = (int)($_POST['id'] ?? 0);
        $request = fetch("SELECT * FROM exchange_requests WHERE id = ? AND (sender_id = ? OR receiver_id = ?) AND status = 'accepted'", 
            [$requestId, $_SESSION['user_id'], $_SESSION['user_id']]);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }
        
        update('exchange_requests', ['status' => 'completed'], 'id = ?', [$requestId]);
        
        // Update listing status
        update('listings', ['status' => 'exchanged'], 'id = ?', [$request['listing_id']]);
        
        // Notify both users
        $otherUserId = $request['sender_id'] == $_SESSION['user_id'] ? $request['receiver_id'] : $request['sender_id'];
        createNotification($otherUserId, 'exchange_completed', 'An exchange has been marked as completed!', $requestId, 'request');
        
        logActivity($_SESSION['user_id'], 'request_completed', 'Exchange marked as completed');
        
        echo json_encode(['success' => true]);
        break;
        
    // ============ RENTAL ACTIONS ============
    case 'rent_accept':
        $requestId = (int)($_POST['id'] ?? 0);
        $request = fetch("SELECT * FROM rentals WHERE id = ? AND owner_id = ? AND status = 'pending'", 
            [$requestId, $_SESSION['user_id']]);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Rental request not found']);
            exit;
        }
        
        update('rentals', ['status' => 'active'], 'id = ?', [$requestId]);
        update('listings', ['status' => 'rented'], 'id = ?', [$request['listing_id']]);
        
        createNotification($request['renter_id'], 'rental_accepted', 
            'Your rental request for "' . fetch("SELECT title FROM listings WHERE id = ?", [$request['listing_id']])['title'] . '" was accepted!',
            $requestId, 'rental');
        
        logActivity($_SESSION['user_id'], 'rent_accepted', 'Accepted rental request');
        
        echo json_encode(['success' => true]);
        break;
        
    case 'rent_reject':
        $requestId = (int)($_POST['id'] ?? 0);
        $request = fetch("SELECT * FROM rentals WHERE id = ? AND owner_id = ? AND status = 'pending'", 
            [$requestId, $_SESSION['user_id']]);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Rental request not found']);
            exit;
        }
        
        update('rentals', ['status' => 'rejected'], 'id = ?', [$requestId]);
        
        createNotification($request['renter_id'], 'rental_rejected', 
            'Your rental request was rejected.', $requestId, 'rental');
        
        logActivity($_SESSION['user_id'], 'rent_rejected', 'Rejected rental request');
        
        echo json_encode(['success' => true]);
        break;
        
    case 'rent_cancel':
        $requestId = (int)($_POST['id'] ?? 0);
        $request = fetch("SELECT * FROM rentals WHERE id = ? AND renter_id = ? AND status = 'pending'", 
            [$requestId, $_SESSION['user_id']]);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Rental request not found']);
            exit;
        }
        
        update('rentals', ['status' => 'cancelled'], 'id = ?', [$requestId]);
        logActivity($_SESSION['user_id'], 'rent_cancelled', 'Cancelled rental request');
        
        echo json_encode(['success' => true]);
        break;
        
    case 'rent_complete':
        $requestId = (int)($_POST['id'] ?? 0);
        $request = fetch("SELECT * FROM rentals WHERE id = ? AND (renter_id = ? OR owner_id = ?) AND status = 'active'", 
            [$requestId, $_SESSION['user_id'], $_SESSION['user_id']]);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Rental request not found']);
            exit;
        }
        
        update('rentals', ['status' => 'returned'], 'id = ?', [$requestId]);
        update('listings', ['status' => 'active'], 'id = ?', [$request['listing_id']]);
        
        $otherUserId = $request['renter_id'] == $_SESSION['user_id'] ? $request['owner_id'] : $request['renter_id'];
        createNotification($otherUserId, 'rental_completed', 'A rental has been marked as returned!', $requestId, 'rental');
        
        logActivity($_SESSION['user_id'], 'rent_completed', 'Rental marked as returned');
        
        echo json_encode(['success' => true]);
        break;
        
    // ============ SHARE ACTIONS ============
    case 'share_approve':
        $requestId = (int)($_POST['id'] ?? 0);
        $request = fetch("SELECT * FROM shares WHERE id = ? AND owner_id = ? AND status = 'pending'", 
            [$requestId, $_SESSION['user_id']]);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Share request not found']);
            exit;
        }
        
        update('shares', ['status' => 'approved'], 'id = ?', [$requestId]);
        update('listings', ['status' => 'shared'], 'id = ?', [$request['listing_id']]);
        
        createNotification($request['requester_id'], 'share_approved', 
            'Your share request for "' . fetch("SELECT title FROM listings WHERE id = ?", [$request['listing_id']])['title'] . '" was approved!',
            $requestId, 'share');
        
        logActivity($_SESSION['user_id'], 'share_approved', 'Approved share request');
        
        echo json_encode(['success' => true]);
        break;
        
    case 'share_reject':
        $requestId = (int)($_POST['id'] ?? 0);
        $request = fetch("SELECT * FROM shares WHERE id = ? AND owner_id = ? AND status = 'pending'", 
            [$requestId, $_SESSION['user_id']]);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Share request not found']);
            exit;
        }
        
        update('shares', ['status' => 'rejected'], 'id = ?', [$requestId]);
        
        createNotification($request['requester_id'], 'share_rejected', 
            'Your share request was rejected.', $requestId, 'share');
        
        logActivity($_SESSION['user_id'], 'share_rejected', 'Rejected share request');
        
        echo json_encode(['success' => true]);
        break;
        
    case 'share_cancel':
        $requestId = (int)($_POST['id'] ?? 0);
        $request = fetch("SELECT * FROM shares WHERE id = ? AND requester_id = ? AND status = 'pending'", 
            [$requestId, $_SESSION['user_id']]);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Share request not found']);
            exit;
        }
        
        update('shares', ['status' => 'cancelled'], 'id = ?', [$requestId]);
        logActivity($_SESSION['user_id'], 'share_cancelled', 'Cancelled share request');
        
        echo json_encode(['success' => true]);
        break;
        
    case 'share_complete':
        $requestId = (int)($_POST['id'] ?? 0);
        $request = fetch("SELECT * FROM shares WHERE id = ? AND (requester_id = ? OR owner_id = ?) AND status = 'approved'", 
            [$requestId, $_SESSION['user_id'], $_SESSION['user_id']]);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Share request not found']);
            exit;
        }
        
        update('shares', ['status' => 'completed'], 'id = ?', [$requestId]);
        update('listings', ['status' => 'active'], 'id = ?', [$request['listing_id']]);
        
        $otherUserId = $request['requester_id'] == $_SESSION['user_id'] ? $request['owner_id'] : $request['requester_id'];
        createNotification($otherUserId, 'share_completed', 'A share has been marked as completed!', $requestId, 'share');
        
        logActivity($_SESSION['user_id'], 'share_completed', 'Share marked as completed');
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
