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
            <a href="inbox.php" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.1); border-radius: 50%">
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
        setInterval(loadSessions, 2000);
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
                
                return `
                <a href="inbox.php?friendId=${f.id}" class="chat-list-item" style="position: relative">
                    <img src="${f.avatar}" alt="av" class="avatar" style="width: 48px; height: 48px" />
                    <div style="flex: 1; min-width: 0">
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

    function formatMessageTime(ms) {
        const date = new Date(ms);
        const now = new Date();
        const isToday = date.getDate() === now.getDate() && 
                        date.getMonth() === now.getMonth() && 
                        date.getFullYear() === now.getFullYear();
        
        if (isToday) {
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
        } else {
            return `${date.toLocaleDateString([], { day: 'numeric', month: 'short' })} ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false })}`;
        }
    }

    // Auto-scroll on input focus (mobile keyboard)
    document.getElementById('messageInput')?.addEventListener('focus', () => {
        setTimeout(scrollToBottom, 300); // Delay for keyboard animation
    });
    
    // Initial scroll
    window.addEventListener('load', scrollToBottom);
</script>

<?php if (!$friendId) include 'footer.php'; else echo '</div></div></body></html>'; ?>

<!-- made by fuad-ismayil -->