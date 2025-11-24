<?php 
$friendId = $_GET['friendId'] ?? null;
include 'header.php'; 
?>

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
        <div class="header" style="justify-content: flex-start; padding-left: 20px; gap: 12px">
            <a href="chat.php" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.1); border-radius: 50%">
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
    
    if (!friendId) {
        renderNav('chat');
        loadSessions();
    } else {
        // Chat Room Logic
        loadFriendDetails();
        loadMessages();
        setInterval(loadMessages, 2000);

        document.getElementById('messageForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('messageInput');
            const text = input.value.trim();
            if (!text) return;

            try {
                await apiCall('messages.php', 'POST', { senderId: user.id, receiverId: friendId, text });
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
                document.getElementById('chat-friend-name').textContent = friend.username;
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
            const friends = await apiCall(`messages.php?action=sessions&userId=${user.id}`);
            const container = document.getElementById('sessions-container');
            const noFriends = document.getElementById('no-friends');

            if (!container) return;

            if (friends.length === 0) {
                if (noFriends) noFriends.style.display = 'block';
                return;
            }

            container.innerHTML = friends.map(f => `
                <a href="chat.php?friendId=${f.id}" class="chat-list-item">
                    <img src="${f.avatar}" alt="av" class="avatar" style="width: 48px; height: 48px" />
                    <div>
                        <div style="font-weight: 600; font-size: 16px; color: #fff">${f.username}</div>
                        <div style="font-size: 14px; color: #666">Tap to chat</div>
                    </div>
                </a>
            `).join('');
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
            const msgs = await apiCall(`messages.php?userId=${user.id}&friendId=${friendId}`);
            const container = document.getElementById('messages-container');
            if (!container) return;
            
            // Simple diffing
            if (container.children.length !== msgs.length) {
                const wasAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;
                
                container.innerHTML = msgs.map(msg => `
                    <div class="message-bubble ${msg.senderId === user.id ? 'msg-own' : 'msg-other'}">
                        ${msg.text}
                    </div>
                `).join('');

                // Auto scroll if new message or first load
                if (wasAtBottom || container.children.length === msgs.length) { 
                    scrollToBottom();
                }
            }
        } catch (err) {
            console.error(err);
        }
    }

    // Auto-scroll on input focus (mobile keyboard)
    document.getElementById('messageInput')?.addEventListener('focus', () => {
        setTimeout(scrollToBottom, 300); // Delay for keyboard animation
    });
    
    // Initial scroll
    window.addEventListener('load', scrollToBottom);
</script>

<?php if (!$friendId) include 'footer.php'; else echo '</div></div><script src="app.js"></script></body></html>'; ?>
