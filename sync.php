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

    updateUserActivity($userId);

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

            $lastActive = $u['last_active'] ?? 0;
            $isOnline = (time() - $lastActive) < 10; // 10 seconds threshold

            $sessions[] = [
                'id' => $u['id'],
                'username' => $u['username'],
                'avatar' => $u['avatar'],
                'lastMessage' => $lastMsg ? $lastMsg['text'] : null,
                'lastMessageTime' => $lastMsg ? $lastMsg['timestamp'] : null,
                'lastMessageSenderId' => $lastMsg ? $lastMsg['sender_id'] : null,
                'lastMessageRead' => $lastMsg ? ($lastMsg['read'] ?? 0) : 0,
                'unreadCount' => $unread,
                'isOnline' => $isOnline
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

    updateUserActivity($userId);
    
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

    // Check if receiver is offline and send push
    $users = readData('users.json');
    $receiver = null;
    $senderName = 'Someone';
    
    foreach ($users as $u) {
        if ($u['id'] === $receiverId) {
            $receiver = $u;
        }
        if ($u['id'] === $senderId) {
            $senderName = $u['username'];
        }
    }

    if ($receiver) {
        $lastActive = $receiver['last_active'] ?? 0;
        $isOffline = (time() - $lastActive) > 10; // 10 seconds threshold

        if ($isOffline) {
            sendOneSignalNotification($receiverId, $senderName, $text);
        }
    }

    echo json_encode(['success' => true]);
}

function sendOneSignalNotification($userId, $senderName, $messageContent) {
    $appId = "{YOUR-APP-ID}";
    $restKey = "{YOUR-REST-API-KEY}";

    $content = [
        "en" => "Message: " . $messageContent
    ];
    
    $headings = [
        "en" => "New message from " . $senderName
    ];

    $fields = [
        'app_id' => $appId,
        'include_aliases' => [
            "external_id" => [$userId]
        ],
        'target_channel' => 'push',
        'contents' => $content,
        'headings' => $headings,
        'url' => 'http://localhost:8000/index.php?page=inbox' // Adjust for production
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $restKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $response = curl_exec($ch);
    curl_close($ch);
    
    error_log("OneSignal Response: " . $response);
}

// made by fuad-ismayil
