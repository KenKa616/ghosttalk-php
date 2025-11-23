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
    $stmt = $db->prepare("SELECT friend_id FROM friends WHERE user_id = ?");
    $stmt->execute([$userId]);
    $friends = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $friends[] = $userId; // Include self

    if (empty($friends)) {
        echo json_encode([]);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($friends), '?'));
    $now = time();

    // Fetch valid posts
    $sql = "SELECT p.*, u.username, u.avatar as userAvatar 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.user_id IN ($placeholders) 
            AND p.expires_at > ? 
            ORDER BY p.created_at DESC";
    
    $params = array_merge($friends, [$now]);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    // Format for frontend
    $formatted = array_map(function($p) {
        return [
            'id' => $p['id'],
            'userId' => $p['user_id'],
            'username' => $p['username'],
            'userAvatar' => $p['userAvatar'],
            'imageUrl' => $p['image_url'],
            'createdAt' => (int)$p['created_at'] * 1000, // JS expects ms
            'expiresAt' => (int)$p['expires_at'] * 1000
        ];
    }, $posts);

    echo json_encode($formatted);

} elseif ($method === 'POST') {
    $data = getJsonInput();
    $userId = $data['userId'] ?? '';
    $imageData = $data['imageUrl'] ?? ''; // Base64 string

    if (!$userId || !$imageData) {
        http_response_code(400);
        exit;
    }

    // Save image to file to avoid bloating DB
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir);
    
    $fileName = generateId() . '.png'; // Assuming PNG for simplicity or extracting from base64 header
    $filePath = $uploadDir . $fileName;
    
    // Simple base64 decode
    $parts = explode(',', $imageData);
    $base64 = count($parts) > 1 ? $parts[1] : $parts[0];
    file_put_contents($filePath, base64_decode($base64));
    
    $publicUrl = '/uploads/' . $fileName;

    $postId = generateId();
    $createdAt = time();
    $expiresAt = $createdAt + 30; // 30 seconds

    $stmt = $db->prepare("INSERT INTO posts (id, user_id, image_url, created_at, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$postId, $userId, $publicUrl, $createdAt, $expiresAt]);

    // Return the new post
    $stmt = $db->prepare("SELECT username, avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

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
?>
