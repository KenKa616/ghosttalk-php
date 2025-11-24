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

    $allFriends = readData('friends.json');
    $users = readData('users.json');
    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = $u;
    }

    $sessions = [];
    foreach ($allFriends as $f) {
        if ($f['user_id'] === $userId && isset($userMap[$f['friend_id']])) {
            $u = $userMap[$f['friend_id']];
            $sessions[] = [
                'id' => $u['id'],
                'username' => $u['username'],
                'avatar' => $u['avatar']
            ];
        }
    }

    echo json_encode($sessions);

} elseif ($method === 'GET' && $action === 'unread') {
    $userId = $_GET['userId'] ?? '';
    if (!$userId) {
        echo json_encode(['count' => 0]);
        exit;
    }
    
    $messages = readData('messages.json');
    $count = 0;
    foreach ($messages as $m) {
        if ($m['receiver_id'] === $userId && ($m['read'] ?? 0) == 0) {
            $count++;
        }
    }
    
    echo json_encode(['count' => $count]);

} elseif ($method === 'GET') {
    // Get messages
    $userId = $_GET['userId'] ?? '';
    $friendId = $_GET['friendId'] ?? '';

    if (!$userId || !$friendId) {
        echo json_encode([]);
        exit;
    }

    $allMessages = readData('messages.json');
    $chatMessages = [];
    $hasUpdates = false;

    foreach ($allMessages as &$m) {
        if (($m['sender_id'] === $userId && $m['receiver_id'] === $friendId) || 
            ($m['sender_id'] === $friendId && $m['receiver_id'] === $userId)) {
            
            // Mark as read if I am the receiver
            if ($m['receiver_id'] === $userId && ($m['read'] ?? 0) == 0) {
                $m['read'] = 1;
                $hasUpdates = true;
            }

            $chatMessages[] = [
                'id' => $m['id'],
                'senderId' => $m['sender_id'],
                'receiverId' => $m['receiver_id'],
                'text' => $m['text'],
                'timestamp' => (int)$m['timestamp'] * 1000
            ];
        }
    }

    if ($hasUpdates) {
        writeData('messages.json', $allMessages);
    }

    // Sort by timestamp
    usort($chatMessages, function($a, $b) {
        return $a['timestamp'] - $b['timestamp'];
    });

    echo json_encode($chatMessages);

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

    $newMessage = [
        'id' => $id,
        'sender_id' => $senderId,
        'receiver_id' => $receiverId,
        'text' => $text,
        'timestamp' => $timestamp,
        'read' => 0
    ];

    $messages = readData('messages.json');
    $messages[] = $newMessage;
    writeData('messages.json', $messages);

    // Debug logging
    error_log("Message saved: " . json_encode($newMessage));

    echo json_encode(['success' => true]);
}
