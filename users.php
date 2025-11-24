<?php
require 'db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'search' && $method === 'GET') {
    $query = $_GET['q'] ?? '';
    $currentUserId = $_GET['userId'] ?? '';

    if (strlen($query) < 2) {
        echo json_encode([]);
        exit;
    }

    $users = readData('users.json');
    $results = [];

    foreach ($users as $u) {
        if ($u['id'] !== $currentUserId && stripos($u['username'], $query) !== false) {
            $results[] = [
                'id' => $u['id'],
                'username' => $u['username'],
                'avatar' => $u['avatar']
            ];
        }
    }

    echo json_encode($results);

} elseif ($action === 'friend' && $method === 'POST') {
    $data = getJsonInput();
    $userId = $data['userId'] ?? '';
    $friendId = $data['friendId'] ?? '';

    if (!$userId || !$friendId) {
        http_response_code(400);
        exit;
    }

    $friends = readData('friends.json');
    
    // Check if already exists
    $exists = false;
    foreach ($friends as $f) {
        if ($f['user_id'] === $userId && $f['friend_id'] === $friendId) {
            $exists = true;
            break;
        }
    }

    if (!$exists) {
        $friends[] = ['user_id' => $userId, 'friend_id' => $friendId];
        $friends[] = ['user_id' => $friendId, 'friend_id' => $userId];
        writeData('friends.json', $friends);
    }

    echo json_encode(['success' => true]);

} elseif ($action === 'avatar' && $method === 'POST') {
    $data = getJsonInput();
    $userId = $data['userId'] ?? '';
    $imageData = $data['avatar'] ?? '';

    if (!$userId || !$imageData) {
        http_response_code(400);
        exit;
    }

    // Save image
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir);
    
    $fileName = 'av_' . $userId . '_' . time() . '.png';
    $filePath = $uploadDir . $fileName;
    
    $parts = explode(',', $imageData);
    $base64 = count($parts) > 1 ? $parts[1] : $parts[0];
    file_put_contents($filePath, base64_decode($base64));
    
    $publicUrl = '/uploads/' . $fileName;

    $users = readData('users.json');
    $updatedUser = null;

    foreach ($users as &$u) {
        if ($u['id'] === $userId) {
            $u['avatar'] = $publicUrl;
            $updatedUser = $u;
            break;
        }
    }
    writeData('users.json', $users);

    // Get friends
    $allFriends = readData('friends.json');
    $friends = [];
    foreach ($allFriends as $f) {
        if ($f['user_id'] === $userId) {
            $friends[] = $f['friend_id'];
        }
    }

    echo json_encode([
        'id' => $updatedUser['id'],
        'username' => $updatedUser['username'],
        'avatar' => $updatedUser['avatar'],
        'inviteCode' => $updatedUser['invite_code'],
        'friends' => $friends
    ]);

} elseif ($action === 'get' && $method === 'GET') {
    $targetId = $_GET['id'] ?? '';
    if (!$targetId) {
        echo json_encode(null);
        exit;
    }

    $users = readData('users.json');
    $foundUser = null;

    foreach ($users as $u) {
        if ($u['id'] === $targetId) {
            $foundUser = $u;
            break;
        }
    }

    if ($foundUser) {
        echo json_encode([
            'id' => $foundUser['id'],
            'username' => $foundUser['username'],
            'avatar' => $foundUser['avatar']
        ]);
    } else {
        echo json_encode(null);
    }
}
