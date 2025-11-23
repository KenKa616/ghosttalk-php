<?php
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'sessions') {
    $userId = $_GET['userId'] ?? '';
    if (!$userId) {
        echo json_encode([]);
        exit;
    }

    // Get friends
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.avatar 
        FROM users u 
        JOIN friends f ON u.id = f.friend_id 
        WHERE f.user_id = ?
    ");
    $stmt->execute([$userId]);
    $friends = $stmt->fetchAll();

    // In a real app we'd attach last message, but for now just friends list as sessions
    echo json_encode($friends);

} elseif ($method === 'GET') {
    // Get messages
    $userId = $_GET['userId'] ?? '';
    $friendId = $_GET['friendId'] ?? '';

    if (!$userId || !$friendId) {
        echo json_encode([]);
        exit;
    }

    // Mark as read
    $updateStmt = $db->prepare("UPDATE messages SET read = 1 WHERE sender_id = ? AND receiver_id = ? AND read = 0");
    $updateStmt->execute([$friendId, $userId]);

    $stmt = $db->prepare("
        SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?) 
        ORDER BY timestamp ASC
    ");
    $stmt->execute([$userId, $friendId, $friendId, $userId]);
    $messages = $stmt->fetchAll();

    $formatted = array_map(function($m) {
        return [
            'id' => $m['id'],
            'senderId' => $m['sender_id'],
            'receiverId' => $m['receiver_id'],
            'text' => $m['text'],
            'timestamp' => (int)$m['timestamp'] * 1000
        ];
    }, $messages);

    echo json_encode($formatted);

} elseif ($method === 'GET' && $action === 'unread') {
    $userId = $_GET['userId'] ?? '';
    if (!$userId) {
        echo json_encode(['count' => 0]);
        exit;
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND read = 0");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    echo json_encode(['count' => (int)$result['count']]);

} elseif ($method === 'POST') {
    // Send message
    $data = getJsonInput();
    $senderId = $data['senderId'] ?? '';
    $receiverId = $data['receiverId'] ?? '';
    $text = $data['text'] ?? '';

    if (!$senderId || !$receiverId || !$text) {
        http_response_code(400);
        exit;
    }

    $id = generateId();
    $timestamp = time();

    $stmt = $db->prepare("INSERT INTO messages (id, sender_id, receiver_id, text, timestamp) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$id, $senderId, $receiverId, $text, $timestamp]);

    echo json_encode(['success' => true]);
}
?>
