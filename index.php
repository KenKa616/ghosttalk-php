<?php 
include 'header.php'; 
$page = $_GET['page'] ?? 'home';
?>

<?php if ($page === 'home'): ?>
    <!-- HOME PAGE -->
    <div class="header">
        Feed
    </div>

    <div class="page-container" id="feed-container">
        <!-- Posts injected here -->
    </div>

    <div id="empty-state" class="flex-center flex-col" style="height: 60vh; color: var(--text-muted); display: none;">
        <p>No ghosts here.</p>
        <p style="font-size: 12px">Tap + to post a 30s photo.</p>
    </div>

    <!-- Upload FAB -->
    <button class="fab" onclick="document.getElementById('fileInput').click()">
        +
    </button>
    <label for="fileInput" class="sr-only">Upload Image</label>
    <input type="file" id="fileInput" style="display: none" accept="image/*" aria-label="Upload Image" onchange="handleFileChange(this)" />

    <script>
        const user = requireAuth();
        renderNav('home');

        async function loadFeed() {
            try {
                const posts = await apiCall(`posts.php?userId=${user.id}`);
                const container = document.getElementById('feed-container');
                const emptyState = document.getElementById('empty-state');
                
                if (!container || !emptyState) return;

                // Client-side filter for expiry to avoid flicker
                const validPosts = posts.filter(p => Date.now() < p.expiresAt);

                if (validPosts.length === 0) {
                    container.innerHTML = '';
                    emptyState.style.display = 'flex';
                    return;
                }

                emptyState.style.display = 'none';
                
                // Simple diffing: if count changed, re-render all (prototype shortcut)
                if (container.children.length !== validPosts.length) {
                    container.innerHTML = validPosts.map(post => createPostHTML(post)).join('');
                    attachPostEvents();
                } else {
                    // Just update timers
                    validPosts.forEach(post => {
                        const timerEl = document.getElementById(`timer-${post.id}`);
                        if (timerEl) {
                            const remaining = Math.ceil((post.expiresAt - Date.now()) / 1000);
                            timerEl.textContent = `${remaining}s`;
                        }
                    });
                }
            } catch (err) {
                console.error(err);
            }
        }

        function createPostHTML(post) {
            const remaining = Math.ceil((post.expiresAt - Date.now()) / 1000);
            return `
                <div class="post-card" id="post-${post.id}">
                    <div class="post-header">
                        <img src="${post.userAvatar}" alt="av" class="avatar" />
                        <span style="font-weight: 600; font-size: 14px">${post.username}</span>
                        <div class="countdown-timer" id="timer-${post.id}">${remaining}s</div>
                    </div>

                    <div class="post-image-container" 
                         onmousedown="reveal(this)" 
                         onmouseup="hide(this)" 
                         onmouseleave="hide(this)"
                         ontouchstart="reveal(this)" 
                         ontouchend="hide(this)">
                        <img src="${post.imageUrl}" alt="Ghost Content" class="post-image" draggable="false" />
                        <div class="hold-hint">Hold to View</div>
                        <div class="watermark" style="display: none">
                            GHOSTTALK • ${post.username} • GHOSTTALK
                        </div>
                    </div>
                </div>
            `;
        }

        function reveal(el) {
            el.querySelector('.post-image').classList.add('revealed');
            el.querySelector('.hold-hint').style.display = 'none';
            el.querySelector('.watermark').style.display = 'block';
        }

        function hide(el) {
            el.querySelector('.post-image').classList.remove('revealed');
            el.querySelector('.hold-hint').style.display = 'block';
            el.querySelector('.watermark').style.display = 'none';
        }

        function attachPostEvents() {
            // Events are inline in HTML string for simplicity in vanilla JS
        }

        async function handleFileChange(input) {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onloadend = async () => {
                if (typeof reader.result === 'string') {
                    try {
                        await apiCall('posts.php', 'POST', { userId: user.id, imageUrl: reader.result });
                        loadFeed();
                    } catch (err) {
                        alert('Failed to post');
                    }
                }
            };
            reader.readAsDataURL(file);
        }

        // Initial load and polling
        if (user) {
            loadFeed();
            setInterval(loadFeed, 1000);
        }
    </script>

<?php elseif ($page === 'search'): ?>
    <!-- SEARCH PAGE -->
    <div class="header">
        <label for="searchInput" class="sr-only">Search users</label>
        <input type="text" id="searchInput" placeholder="Search users..." aria-label="Search users" style="width: 90%; margin: 0; background: var(--surface-light); border: none; border-radius: 20px" autofocus />
    </div>

    <div class="page-container">
        <div id="results-container">
        </div>
        <p id="no-results" style="color: #444; text-align: center; margin-top: 20px; display: none">No users found.</p>
    </div>

    <script>
        const user = requireAuth();
        renderNav('search');

        const searchInput = document.getElementById('searchInput');
        const container = document.getElementById('results-container');
        const noResults = document.getElementById('no-results');

        searchInput.addEventListener('input', async (e) => {
            const query = e.target.value;
            if (query.length < 2) {
                container.innerHTML = '';
                noResults.style.display = 'none';
                return;
            }

            try {
                const results = await apiCall(`users.php?action=search&q=${query}&userId=${user.id}`);
                
                if (results.length === 0) {
                    container.innerHTML = '';
                    noResults.style.display = 'block';
                } else {
                    noResults.style.display = 'none';
                    container.innerHTML = results.map(u => `
                        <div class="flex-between" style="padding: 15px 0; border-bottom: 1px solid #111">
                            <div class="flex-center" style="gap: 10px">
                                <img src="${u.avatar}" alt="av" class="avatar" />
                                <span>${u.username}</span>
                            </div>
                            <button onclick="addFriend('${u.id}')" style="padding: 6px 12px; background: #fff; color: #000; border-radius: 6px; font-weight: bold; font-size: 12px">
                                Add
                            </button>
                        </div>
                    `).join('');
                }
            } catch (err) {
                console.error(err);
            }
        });

        async function addFriend(friendId) {
            try {
                await apiCall('users.php?action=friend', 'POST', { userId: user.id, friendId });
                alert('Friend added!');
            } catch (err) {
                alert('Failed to add friend');
            }
        }
    </script>

<?php elseif ($page === 'inbox'): ?>
    <!-- INBOX PAGE -->
    <?php $friendId = $_GET['friendId'] ?? null; ?>
    
    <!-- Chat List View -->
    <div id="chat-list-view" style="display: <?php echo $friendId ? 'none' : 'block'; ?>">
        <header class="header">Messages</header>
        <div class="page-container" style="padding: 0">
            <div id="sessions-container">
                <!-- Sessions injected here -->
            </div>
            <div id="no-friends" style="padding: 20px; color: #666; text-align: center; display: none">
                No friends yet. Go to Search to find people.
            </div>
        </div>
    </div>

    <!-- Chat Room View -->
    <div id="chat-room-view" style="display: <?php echo $friendId ? 'flex' : 'none'; ?>; flex-direction: column; height: 100%">
        <?php if ($friendId): ?>
            <style>.bottom-nav { display: none !important; }</style>
            <div class="header" style="justify-content: flex-start; padding-left: 20px; gap: 12px">
                <a href="index.php?page=inbox" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.1); border-radius: 50%">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                </a>
                <img id="chat-friend-avatar" src="" alt="av" class="avatar" style="display: none" />
                <span id="chat-friend-name" style="font-size: 16px; font-weight: 600">Chat</span>
            </div>
        <?php endif; ?> 

        <div id="chat-scroll-container" class="page-container" style="display: flex; flex-direction: column; padding-bottom: 90px; flex: 1">
            <div id="messages-container" style="flex: 1; display: flex; flex-direction: column;">
                <!-- Messages injected here -->
            </div>
        </div>

        <form id="messageForm" style="padding: 16px; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); display: flex; gap: 12px; position: fixed; bottom: 0; width: 100%; max-width: 480px; z-index: 100; border-top: 1px solid rgba(255,255,255,0.05)">
            <label for="messageInput" class="sr-only">Message</label>
            <input type="text" id="messageInput" placeholder="Type a message..." aria-label="Message" style="margin-bottom: 0; border-radius: 24px; background: var(--surface-light); border: none; padding: 12px 20px" autocomplete="off" />
            <button type="submit" style="width: 48px; height: 48px; border-radius: 50%; background: var(--primary-gradient); color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>
        </form>
    </div>

    <script>
        const user = requireAuth();
        const friendId = "<?php echo $friendId; ?>";
        
        // Render Bottom Nav
        renderNav('inbox');

        if (!friendId) {
            loadSessions();
            setInterval(loadSessions, 2000);
        } else {
            // Chat Room Logic
            loadFriendDetails();
            loadMessages();
            setInterval(loadMessages, 2000);
            setInterval(loadFriendDetails, 5000);

            document.getElementById('messageForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const input = document.getElementById('messageInput');
                const text = input.value.trim();
                if (!text) return;

                try {
                    await apiCall('sync.php', 'POST', { senderId: user.id, receiverId: friendId, text });
                    input.value = '';
                    loadMessages();
                } catch (err) {
                    console.error(err);
                }
            });
        }

        async function loadFriendDetails() {
            try {
                const friend = await apiCall(`users.php?action=get&id=${friendId}`);
                if (friend) {
                    document.getElementById('chat-friend-name').innerHTML = `
                        ${friend.username}
                        <span style="font-size: 12px; color: ${friend.isOnline ? '#10b981' : '#666'}; font-weight: normal; margin-left: 8px">
                            ${friend.isOnline ? 'Online' : 'Offline'}
                        </span>
                    `;
                    const avatarEl = document.getElementById('chat-friend-avatar');
                    avatarEl.src = friend.avatar;
                    avatarEl.style.display = 'block';
                }
            } catch (err) {
                console.error('Failed to load friend details');
            }
        }

        async function loadSessions() {
            try {
                const friends = await apiCall(`sync.php?action=sessions&userId=${user.id}`);
                const container = document.getElementById('sessions-container');
                const noFriends = document.getElementById('no-friends');

                if (!container) return;

                if (friends.length === 0) {
                    if (noFriends) noFriends.style.display = 'block';
                    return;
                }

                container.innerHTML = friends.map(f => {
                    const time = f.lastMessageTime ? formatListTime(f.lastMessageTime * 1000) : '';
                    const isMe = f.lastMessageSenderId === user.id;
                    const check = isMe ? (f.lastMessageRead ? '<span style="color: #fff">✓✓</span>' : '✓') : '';
                    const preview = f.lastMessage ? (isMe ? `${check} ${f.lastMessage}` : f.lastMessage) : 'Tap to chat';
                    const unreadDot = f.unreadCount > 0 ? `<div style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); width: 8px; height: 8px; background: #fff; border-radius: 50%;"></div>` : '';
                    const onlineDot = f.isOnline ? `<div style="width: 10px; height: 10px; background: #10b981; border-radius: 50%; border: 2px solid #000; position: absolute; bottom: 0; right: 0"></div>` : '';

                    return `
                    <a href="index.php?page=inbox&friendId=${f.id}" class="chat-list-item" style="position: relative">
                        <div style="position: relative; width: 48px; height: 48px">
                            <img src="${f.avatar}" alt="av" class="avatar" style="width: 100%; height: 100%" />
                            ${onlineDot}
                        </div>
                        <div style="flex: 1; min-width: 0; margin-left: 12px">
                            <div style="display: flex; justify-content: space-between; align-items: center">
                                <div style="font-weight: 600; font-size: 16px; color: #fff">${f.username}</div>
                                <div style="font-size: 12px; color: #999">${time}</div>
                            </div>
                            <div style="font-size: 14px; color: #999; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 4px">
                                ${preview}
                            </div>
                        </div>
                        ${unreadDot}
                    </a>
                    `;
                }).join('');
            } catch (err) {
                console.error(err);
            }
        }

        function scrollToBottom() {
            const container = document.getElementById('chat-scroll-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        async function loadMessages() {
            if (!friendId) return;
            try {
                const msgs = await apiCall(`sync.php?userId=${user.id}&friendId=${friendId}`);
                const container = document.getElementById('messages-container');
                if (!container) return;
                
                const newJson = JSON.stringify(msgs);
                if (newJson !== window.lastMsgsJson) {
                    window.lastMsgsJson = newJson;
                    const wasAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;
                    
                    container.innerHTML = msgs.map(msg => {
                        const isOwn = msg.senderId === user.id;
                        const time = formatMessageTime(msg.timestamp);
                        const check = isOwn ? (msg.read ? '<span style="color: #000080; font-weight: 900; margin-left: 4px">✓✓</span>' : '<span style="color: #000080; font-weight: 900; margin-left: 4px">✓</span>') : '';
                        
                        return `
                        <div class="message-bubble ${isOwn ? 'msg-own' : 'msg-other'}" style="display: flex; flex-direction: column; align-items: ${isOwn ? 'flex-end' : 'flex-start'}">
                            <div>${msg.text}</div>
                            <div style="font-size: 10px; opacity: 0.7; margin-top: 4px; display: flex; align-items: center">
                                ${time} ${check}
                            </div>
                        </div>
                        `;
                    }).join('');

                    // Auto scroll if new message or first load
                    if (wasAtBottom || container.children.length === msgs.length) { 
                        scrollToBottom();
                    }
                }
            } catch (err) {
                console.error(err);
            }
        }

        function formatMessageTime(ms) {
            const date = new Date(ms);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
        }

        function formatListTime(ms) {
            const date = new Date(ms);
            const now = new Date();
            const isToday = date.getDate() === now.getDate() && 
                            date.getMonth() === now.getMonth() && 
                            date.getFullYear() === now.getFullYear();
            
            if (isToday) {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
            } else {
                return date.toLocaleDateString([], { day: 'numeric', month: 'short' });
            }
        }
    </script>

<?php elseif ($page === 'profile'): ?>
    <!-- PROFILE PAGE -->
    <div class="page-container flex-col flex-center" style="position: relative">
        <div style="position: absolute; top: 20px; right: 20px; cursor: pointer" onclick="openSettings()">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
        </div>

        <div class="avatar-wrapper" onclick="document.getElementById('avatarInput').click()">
            <img id="profile-avatar" src="" alt="Profile" class="profile-avatar-large" />
            <div class="avatar-overlay">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            </div>
        </div>
        <label for="avatarInput" class="sr-only">Upload Avatar</label>
        <input type="file" id="avatarInput" style="display: none" accept="image/*" aria-label="Upload Avatar" onchange="handleAvatarChange(this)" />

        <h2 id="profile-username" style="font-size: 24px; margin-bottom: 8px"></h2>
        
        <div style="background: var(--surface-color); padding: 16px; border-radius: 16px; margin-bottom: 24px; border: 1px solid var(--border-color); display: inline-block; min-width: 200px; text-align: center">
            <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px">Invite Code</div>
            <div id="profile-code" style="font-size: 20px; font-weight: 700; color: var(--primary-color); letter-spacing: 2px"></div>
        </div>

        <div style="margin-top: 20px; width: 100%; display: flex; gap: 10px">
            <div style="flex: 1; background: var(--surface-color); padding: 15px; border-radius: 12px; text-align: center; border: 1px solid var(--border-color)">
                <div id="friend-count" style="font-size: 20px; font-weight: bold">0</div>
                <div style="color: var(--text-muted); font-size: 12px">Friends</div>
            </div>
        </div>

        <button onclick="handleLogout()" class="primary-btn" style="margin-top: 40px; background: var(--surface-light); color: var(--danger-color); max-width: 200px; margin: 40px auto 0">
            Log Out
        </button>
    </div>

    <!-- Settings Modal -->
    <div id="settings-modal" class="modal-overlay">
        <div class="modal-content">
            <button onclick="closeSettings()" class="modal-close-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            
            <h3 class="modal-title">Settings</h3>

            <!-- Password Reset -->
            <div class="modal-section">
                <h4 class="modal-label">Change Password</h4>
                <form onsubmit="event.preventDefault(); updatePassword()">
                    <input type="password" id="oldPass" placeholder="Old Password" autocomplete="current-password" style="margin-bottom: 8px; font-size: 14px" />
                    <input type="password" id="newPass" placeholder="New Password" autocomplete="new-password" style="margin-bottom: 8px; font-size: 14px" />
                    <input type="password" id="newPass2" placeholder="Repeat New Password" autocomplete="new-password" style="margin-bottom: 12px; font-size: 14px" />
                    <button type="submit" class="primary-btn" style="padding: 12px; font-size: 13px">Update Password</button>
                </form>
            </div>

            <!-- Username Change -->
            <div class="modal-section">
                <h4 class="modal-label">Change Username</h4>
                <form onsubmit="event.preventDefault(); updateUsername()">
                    <div style="position: relative; margin-bottom: 12px">
                        <input type="text" id="newUsername" placeholder="New Username" autocomplete="username" style="margin-bottom: 0; padding-right: 40px; font-size: 14px" />
                        <div id="modal-username-indicator" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-weight: bold; display: none"></div>
                    </div>
                    <button type="submit" class="primary-btn" style="padding: 12px; font-size: 13px">Update Username</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const user = requireAuth();
        renderNav('profile');

        // Render initial data
        document.getElementById('profile-avatar').src = user.avatar;
        document.getElementById('profile-username').textContent = user.username;
        document.getElementById('profile-code').textContent = user.inviteCode;
        document.getElementById('friend-count').textContent = user.friends.length;

        async function handleAvatarChange(input) {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onloadend = async () => {
                if (typeof reader.result === 'string') {
                    try {
                        const updatedUser = await apiCall('users.php?action=avatar', 'POST', { 
                            userId: user.id, 
                            avatar: reader.result 
                        });
                        setSession(updatedUser);
                        // Update UI
                        document.getElementById('profile-avatar').src = updatedUser.avatar;
                    } catch (err) {
                        alert('Failed to update avatar');
                    }
                }
            };
            reader.readAsDataURL(file);
        }

        function handleLogout() {
            clearSession();
            window.location.href = 'login.php';
        }

        // Settings Modal Logic
        function openSettings() {
            const modal = document.getElementById('settings-modal');
            modal.style.display = 'flex';
            // Small delay to allow display:flex to apply before adding show class for transition
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }

        function closeSettings() {
            const modal = document.getElementById('settings-modal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300); // Match transition duration
        }

        async function updatePassword() {
            const oldPassword = document.getElementById('oldPass').value;
            const newPassword = document.getElementById('newPass').value;
            const newPass2 = document.getElementById('newPass2').value;

            if (newPassword !== newPass2) {
                alert('New passwords do not match');
                return;
            }

            try {
                await apiCall('auth.php?action=update_password', 'POST', {
                    userId: user.id,
                    oldPassword,
                    newPassword
                });
                alert('Password updated successfully');
                document.getElementById('oldPass').value = '';
                document.getElementById('newPass').value = '';
                document.getElementById('newPass2').value = '';
            } catch (err) {
                alert(err.message);
            }
        }

        const newUsernameInput = document.getElementById('newUsername');
        const modalIndicator = document.getElementById('modal-username-indicator');

        newUsernameInput.addEventListener('input', async (e) => {
            const username = e.target.value.trim();
            if (username.length < 3) {
                modalIndicator.style.display = 'none';
                return;
            }

            try {
                const res = await apiCall(`auth.php?action=check_username&username=${encodeURIComponent(username)}`);
                modalIndicator.style.display = 'block';
                if (res.available) {
                    modalIndicator.textContent = '✓';
                    modalIndicator.style.color = '#4ade80';
                } else {
                    modalIndicator.textContent = '✕';
                    modalIndicator.style.color = '#ef4444';
                }
            } catch (err) {
                console.error(err);
            }
        });

        async function updateUsername() {
            const newUsername = newUsernameInput.value.trim();
            if (!newUsername) return;

            try {
                const updatedUser = await apiCall('auth.php?action=update_username', 'POST', {
                    userId: user.id,
                    newUsername
                });
                setSession(updatedUser);
                document.getElementById('profile-username').textContent = updatedUser.username;
                alert('Username updated successfully');
                closeSettings();
            } catch (err) {
                alert(err.message);
            }
        }
    </script>
<?php endif; ?>

<?php include 'footer.php'; ?>

<!-- made by fuad-ismayil -->