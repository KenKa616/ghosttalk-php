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
        <div class="header" style="justify-content: flex-start; padding-left: 15px; position: sticky; top: 0">
            <a href="chat.php" style="background: none; color: #fff; margin-right: 10px; font-size: 20px">←</a>
            <span id="chat-friend-name">Chat</span>
        </div>
    <?php endif; ?>

    <div id="chat-scroll-container" class="page-container" style="display: flex; flex-direction: column; padding-bottom: 0; flex: 1">
        <div id="messages-container" style="flex: 1; display: flex; flex-direction: column;">
            <!-- Messages injected here -->
        </div>
    </div>

    <form id="messageForm" style="padding: 10px; background: #000; border-top: 1px solid #222; display: flex; gap: 10px; position: sticky; bottom: 0">
        <label for="messageInput" style="display: none">Message</label>
        <input type="text" id="messageInput" placeholder="Message..." aria-label="Message" style="margin-bottom: 0; border-radius: 20px" autocomplete="off" />
        <button type="submit" style="color: #fff; background: none; font-weight: bold">Send</button>
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
        loadMessages();
        setInterval(loadMessages, 2000);

        document.getElementById('messageForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('messageInput');
            const text = input.value.trim();
            if (!text) return;

            try {
                await apiCall('chat.php', 'POST', { senderId: user.id, receiverId: friendId, text });
                input.value = '';
                loadMessages();
            } catch (err) {
                console.error(err);
            }
        });
    }

    async function loadSessions() {
        try {
            const friends = await apiCall(`chat.php?action=sessions&userId=${user.id}`);
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
            const msgs = await apiCall(`chat.php?userId=${user.id}&friendId=${friendId}`);
            const container = document.getElementById('messages-container');
            if (!container) return;
            
            // Simple diffing
            if (container.children.length !== msgs.length) {
                const wasAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;
                
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
