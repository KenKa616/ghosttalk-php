<?php
require 'db.php';

$action = $_GET['action'] ?? '';

// made by fuad-ismayil

if ($action === 'check_username') {
    $username = $_GET['username'] ?? '';
    if (strlen($username) < 3) {
        echo json_encode(['available' => false]);
        exit;
    }

    $users = readData('users.json');
    $available = true;
    foreach ($users as $u) {
        if (strcasecmp($u['username'], $username) === 0) {
            $available = false;
            break;
        }
    }
    echo json_encode(['available' => $available]);

} elseif ($action === 'register') {
    $data = getJsonInput();
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $inviteCode = $data['inviteCode'] ?? '';

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters']);
        exit;
    }

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
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
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
    $password = $data['password'] ?? '';

    $users = readData('users.json');
    $user = null;
    foreach ($users as $u) {
        if (strcasecmp($u['username'], $username) === 0) {
            $user = $u;
            break;
        }
    }

    if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
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

} elseif ($action === 'update_password') {
    $data = getJsonInput();
    $userId = $data['userId'] ?? '';
    $oldPassword = $data['oldPassword'] ?? '';
    $newPassword = $data['newPassword'] ?? '';

    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'New password too short']);
        exit;
    }

    $users = readData('users.json');
    $updated = false;

    foreach ($users as &$u) {
        if ($u['id'] === $userId) {
            if (!password_verify($oldPassword, $u['password_hash'] ?? '')) {
                http_response_code(401);
                echo json_encode(['error' => 'Incorrect old password']);
                exit;
            }
            $u['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $updated = true;
            break;
        }
    }

    if ($updated) {
        writeData('users.json', $users);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }

} elseif ($action === 'update_username') {
    $data = getJsonInput();
    $userId = $data['userId'] ?? '';
    $newUsername = $data['newUsername'] ?? '';

    if (strlen($newUsername) < 3) {
        http_response_code(400);
        echo json_encode(['error' => 'Username too short']);
        exit;
    }

    $users = readData('users.json');
    
    // Check availability
    foreach ($users as $u) {
        if ($u['id'] !== $userId && strcasecmp($u['username'], $newUsername) === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Username taken']);
            exit;
        }
    }

    $updatedUser = null;
    foreach ($users as &$u) {
        if ($u['id'] === $userId) {
            $u['username'] = $newUsername;
            // Update avatar seed too? Maybe keep old one. Let's update it to match new identity.
            $u['avatar'] = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($newUsername);
            $updatedUser = $u;
            break;
        }
    }

    if ($updatedUser) {
        writeData('users.json', $users);
        
        // Get friends for session update
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
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }

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