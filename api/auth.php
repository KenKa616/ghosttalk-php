<?php
require 'db.php';

$action = $_GET['action'] ?? '';

if ($action === 'register') {
    $data = getJsonInput();
    $username = $data['username'] ?? '';
    $inviteCode = $data['inviteCode'] ?? '';

    // Check if username exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? COLLATE NOCASE");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Username taken']);
        exit;
    }

    // Validate Invite Code
    $valid = false;
    $inviterId = null;
    
    if ($inviteCode === 'GHOST' || $inviteCode === '616') {
        $valid = true;
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE invite_code = ?");
        $stmt->execute([$inviteCode]);
        $inviter = $stmt->fetch();
        if ($inviter) {
            $valid = true;
            $inviterId = $inviter['id'];
        }
    }

    if (!$valid) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid invite code']);
        exit;
    }

    $userId = generateId();
    $newInviteCode = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $avatar = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($username);

    $stmt = $db->prepare("INSERT INTO users (id, username, avatar, invite_code, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $username, $avatar, $newInviteCode, time()]);

    // Add friend connection if invited by user
    if ($inviterId) {
        $db->prepare("INSERT INTO friends (user_id, friend_id) VALUES (?, ?)")->execute([$userId, $inviterId]);
        $db->prepare("INSERT INTO friends (user_id, friend_id) VALUES (?, ?)")->execute([$inviterId, $userId]);
    }

    echo json_encode([
        'id' => $userId,
        'username' => $username,
        'avatar' => $avatar,
        'inviteCode' => $newInviteCode,
        'friends' => []
    ]);

} elseif ($action === 'login') {
    $data = getJsonInput();
    $username = $data['username'] ?? '';

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? COLLATE NOCASE");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

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

} elseif ($action === 'me') {
    // In a real app we'd check session/cookie. 
    // For this prototype, the frontend sends the ID.
    $id = $_GET['id'] ?? '';
    if (!$id) {
        echo json_encode(null);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if ($user) {
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
    } else {
        echo json_encode(null);
    }
}
?>
