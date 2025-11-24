<?php
require 'db.php';

$action = $_GET['action'] ?? '';

if ($action === 'register') {
    $data = getJsonInput();
    $username = $data['username'] ?? '';
    $inviteCode = $data['inviteCode'] ?? '';

    $users = readData('users.json');

    // Check if username exists
    foreach ($users as $u) {
        if (strcasecmp($u['username'], $username) === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Username taken']);
            exit;
        }
    }

    // Validate Invite Code
    $valid = false;
    $inviterId = null;
    
    if ($inviteCode === 'GHOST' || $inviteCode === '616') {
        $valid = true;
    } else {
        foreach ($users as $u) {
            if ($u['invite_code'] === $inviteCode) {
                $valid = true;
                $inviterId = $u['id'];
                break;
            }
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

    $newUser = [
        'id' => $userId,
        'username' => $username,
        'avatar' => $avatar,
        'invite_code' => $newInviteCode,
        'created_at' => time()
    ];

    $users[] = $newUser;
    writeData('users.json', $users);

    // Add friend connection if invited by user
    if ($inviterId) {
        $friends = readData('friends.json');
        $friends[] = ['user_id' => $userId, 'friend_id' => $inviterId];
        $friends[] = ['user_id' => $inviterId, 'friend_id' => $userId];
        writeData('friends.json', $friends);
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

    $users = readData('users.json');
    $user = null;
    foreach ($users as $u) {
        if (strcasecmp($u['username'], $username) === 0) {
            $user = $u;
            break;
        }
    }

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Get friends
    $allFriends = readData('friends.json');
    $friends = [];
    foreach ($allFriends as $f) {
        if ($f['user_id'] === $user['id']) {
            $friends[] = $f['friend_id'];
        }
    }

    echo json_encode([
        'id' => $user['id'],
        'username' => $user['username'],
        'avatar' => $user['avatar'],
        'inviteCode' => $user['invite_code'],
        'friends' => $friends
    ]);

} elseif ($action === 'me') {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        echo json_encode(null);
        exit;
    }

    $users = readData('users.json');
    $user = null;
    foreach ($users as $u) {
        if ($u['id'] === $id) {
            $user = $u;
            break;
        }
    }

    if ($user) {
        $allFriends = readData('friends.json');
        $friends = [];
        foreach ($allFriends as $f) {
            if ($f['user_id'] === $user['id']) {
                $friends[] = $f['friend_id'];
            }
        }

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

// made by fuad-ismayil