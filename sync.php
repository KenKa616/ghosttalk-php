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

    $allMessages = readData('messages.json');
    $sessions = [];
    foreach ($allFriends as $f) {
        if ($f['user_id'] === $userId && isset($userMap[$f['friend_id']])) {
            $u = $userMap[$f['friend_id']];
            $friendId = $u['id'];

            // Find last message and unread count
            $lastMsg = null;
            $unread = 0;
            foreach ($allMessages as $m) {
                if (($m['sender_id'] === $userId && $m['receiver_id'] === $friendId) || 
                    ($m['sender_id'] === $friendId && $m['receiver_id'] === $userId)) {
                    
                    if (!$lastMsg || $m['timestamp'] > $lastMsg['timestamp']) {
                        $lastMsg = $m;
                    }
                    
                    if ($m['sender_id'] === $friendId && ($m['read'] ?? 0) == 0) {
                        $unread++;
                    }
                }
            }

            $sessions[] = [
                'id' => $u['id'],
                'username' => $u['username'],
                'avatar' => $u['avatar'],
                'lastMessage' => $lastMsg ? $lastMsg['text'] : null,
                'lastMessageTime' => $lastMsg ? $lastMsg['timestamp'] : null,
                'lastMessageSenderId' => $lastMsg ? $lastMsg['sender_id'] : null,
                'lastMessageRead' => $lastMsg ? ($lastMsg['read'] ?? 0) : 0,
                'unreadCount' => $unread
            ];
        }
    }

    // Sort by last message time desc
    usort($sessions, function($a, $b) {
        return ($b['lastMessageTime'] ?? 0) - ($a['lastMessageTime'] ?? 0);
    });

    echo json_encode($sessions);

} elseif ($method === 'GET' && $action === 'unread') {
    $userId = $_GET['userId'] ?? '';
    if (!$userId) {
        echo json_encode(['count' => 0]);
        exit;
    }
    
    $messages = readData('messages.json');
    $users = readData('users.json');
    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = $u;
    }

    $count = 0;
    $latestMsg = null;

    foreach ($messages as $m) {
        if ($m['receiver_id'] === $userId && ($m['read'] ?? 0) == 0) {
            $count++;
            if (!$latestMsg || $m['timestamp'] > $latestMsg['timestamp']) {
                $latestMsg = $m;
            }
        }
    }
    
    $response = ['count' => $count];
    if ($latestMsg && isset($userMap[$latestMsg['sender_id']])) {
        $sender = $userMap[$latestMsg['sender_id']];
        $response['latestMessage'] = [
            'id' => $latestMsg['id'],
            'text' => $latestMsg['text'],
            'senderId' => $sender['id'],
            'senderName' => $sender['username'],
            'senderAvatar' => $sender['avatar']
        ];
    }
    
    echo json_encode($response);

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
                'timestamp' => (int)$m['timestamp'] * 1000,
                'read' => $m['read'] ?? 0
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

// made by fuad-ismayil
