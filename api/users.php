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

    $stmt = $db->prepare("SELECT id, username, avatar FROM users WHERE username LIKE ? AND id != ?");
    $stmt->execute(["%$query%", $currentUserId]);
    $users = $stmt->fetchAll();

    echo json_encode($users);

} elseif ($action === 'friend' && $method === 'POST') {
    $data = getJsonInput();
    $userId = $data['userId'] ?? '';
    $friendId = $data['friendId'] ?? '';

    if (!$userId || !$friendId) {
        http_response_code(400);
        exit;
    }

    // Mutual add for prototype
    try {
        $db->prepare("INSERT OR IGNORE INTO friends (user_id, friend_id) VALUES (?, ?)")->execute([$userId, $friendId]);
        $db->prepare("INSERT OR IGNORE INTO friends (user_id, friend_id) VALUES (?, ?)")->execute([$friendId, $userId]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'avatar' && $method === 'POST') {
    $data = getJsonInput();
    $userId = $data['userId'] ?? '';
    $imageData = $data['avatar'] ?? '';

    if (!$userId || !$imageData) {
        http_response_code(400);
        exit;
    }

    // Save image
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir);
    
    $fileName = 'av_' . $userId . '_' . time() . '.png';
    $filePath = $uploadDir . $fileName;
    
    $parts = explode(',', $imageData);
    $base64 = count($parts) > 1 ? $parts[1] : $parts[0];
    file_put_contents($filePath, base64_decode($base64));
    
    $publicUrl = '/uploads/' . $fileName;

    $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->execute([$publicUrl, $userId]);

    // Return updated user
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Get friends
    $stmt = $db->prepare("SELECT friend_id FROM friends WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $friends = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'id' => $user['id'],
        'username' => $user['username'],
        'avatar' => $user['avatar'],
        'inviteCode' => $user['invite_code'],
        'friends' => $friends
    ]);
}
?>
