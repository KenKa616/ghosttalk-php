<?php
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $userId = $_GET['userId'] ?? '';
    if (!$userId) {
        echo json_encode([]);
        exit;
    }

    // Get current user's friends
    $allFriends = readData('friends.json');
    $friendIds = [$userId]; // Include self
    foreach ($allFriends as $f) {
        if ($f['user_id'] === $userId) {
            $friendIds[] = $f['friend_id'];
        }
    }

    $allPosts = readData('posts.json');
    $users = readData('users.json');
    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = $u;
    }

    $now = time();
    $validPosts = [];

    foreach ($allPosts as $p) {
        if (in_array($p['user_id'], $friendIds) && $p['expires_at'] > $now) {
            $user = $userMap[$p['user_id']] ?? ['username' => 'Unknown', 'avatar' => ''];
            $validPosts[] = [
                'id' => $p['id'],
                'userId' => $p['user_id'],
                'username' => $user['username'],
                'userAvatar' => $user['avatar'],
                'imageUrl' => $p['image_url'],
                'createdAt' => (int)$p['created_at'] * 1000,
                'expiresAt' => (int)$p['expires_at'] * 1000
            ];
        }
    }

    // Sort by created_at DESC
    usort($validPosts, function($a, $b) {
        return $b['createdAt'] - $a['createdAt'];
    });

    echo json_encode($validPosts);

} elseif ($method === 'POST') {
    $data = getJsonInput();
    $userId = $data['userId'] ?? '';
    $imageData = $data['imageUrl'] ?? ''; // Base64 string

    if (!$userId || !$imageData) {
        http_response_code(400);
        exit;
    }

    // Save image to file
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir);
    
    $fileName = generateId() . '.png';
    $filePath = $uploadDir . $fileName;
    
    $parts = explode(',', $imageData);
    $base64 = count($parts) > 1 ? $parts[1] : $parts[0];
    file_put_contents($filePath, base64_decode($base64));
    
    $publicUrl = '/uploads/' . $fileName;

    $postId = generateId();
    $createdAt = time();
    $expiresAt = $createdAt + 30; // 30 seconds

    $newPost = [
        'id' => $postId,
        'user_id' => $userId,
        'image_url' => $publicUrl,
        'created_at' => $createdAt,
        'expires_at' => $expiresAt
    ];

    $posts = readData('posts.json');
    $posts[] = $newPost;
    writeData('posts.json', $posts);

    // Return the new post
    $users = readData('users.json');
    $user = null;
    foreach ($users as $u) {
        if ($u['id'] === $userId) {
            $user = $u;
            break;
        }
    }

    echo json_encode([
        'id' => $postId,
        'userId' => $userId,
        'username' => $user['username'],
        'userAvatar' => $user['avatar'],
        'imageUrl' => $publicUrl,
        'createdAt' => $createdAt * 1000,
        'expiresAt' => $expiresAt * 1000
    ]);
}
