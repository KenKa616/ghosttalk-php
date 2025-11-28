<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#000000">
    <title>GhostTalk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        .msg-popup {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 12px 16px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            width: 90%;
            max-width: 400px;
        }
        .msg-popup.show {
            transform: translateX(-50%) translateY(0);
        }
        .popup-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .popup-content {
            flex: 1;
            min-width: 0;
        }
        .popup-name {
            font-weight: 600;
            font-size: 14px;
            color: #fff;
            margin-bottom: 2px;
        }
        .popup-text {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
    <script>
        var API_URL = '';

        // --- AUTH UTILS ---
        function getSession() {
            const user = localStorage.getItem('gt_user');
            return user ? JSON.parse(user) : null;
        }

        function setSession(user) {
            localStorage.setItem('gt_user', JSON.stringify(user));
        }

        function clearSession() {
            localStorage.removeItem('gt_user');
        }

        function requireAuth() {
            const user = getSession();
            if (!user) {
                window.location.href = 'login.php';
            }
            return user;
        }

        // --- API WRAPPER ---
        async function apiCall(endpoint, method = 'GET', body = null) {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };
            if (body) options.body = JSON.stringify(body);

            const res = await fetch(`${API_URL}${endpoint}`, options);
            if (!res.ok) {
                const err = await res.json();
                throw new Error(err.error || 'API Error');
            }
            return res.json();
        }

        // --- UI UTILS ---
        async function renderNav(activePage) {
            const nav = document.createElement('div');
            nav.className = 'bottom-nav';
            nav.innerHTML = `
                <a href="index.php?page=home" class="nav-item ${activePage === 'home' ? 'active' : ''}">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                </a>
                <a href="index.php?page=search" class="nav-item ${activePage === 'search' ? 'active' : ''}">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </a>
                <a href="index.php?page=inbox" class="nav-item ${activePage === 'inbox' ? 'active' : ''}" style="position: relative">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <div id="nav-unread-dot" class="unread-dot"></div>
                </a>
                <a href="index.php?page=profile" class="nav-item ${activePage === 'profile' ? 'active' : ''}">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </a>
            `;
            document.getElementById('root').appendChild(nav);

            // Check unread
            let lastNotifiedId = null;
            let initialized = false;

            async function checkUnread() {
                const user = getSession();
                if (!user) return;
                try {
                    const unread = await apiCall(`sync.php?action=unread&userId=${user.id}`);
                    const dot = document.getElementById('nav-unread-dot');
                    if (dot) {
                        dot.style.display = unread.count > 0 ? 'block' : 'none';
                    }

                    // Popup Logic
                    if (!initialized) {
                        // First load: just sync the ID, don't show popup
                        if (unread.latestMessage) {
                            lastNotifiedId = unread.latestMessage.id;
                        }
                        initialized = true;
                    } else {
                        // Subsequent checks
                        if (unread.latestMessage && unread.latestMessage.id !== lastNotifiedId) {
                            // New message detected!
                            showPopup(unread.latestMessage);
                            lastNotifiedId = unread.latestMessage.id;
                        }
                    }

                } catch (e) {
                    console.error('Unread check failed', e);
                }
            }

            function showPopup(msg) {
                const popup = document.getElementById('msg-popup');
                if (!popup) return;
                
                document.getElementById('popup-avatar').src = msg.senderAvatar;
                document.getElementById('popup-name').textContent = msg.senderName;
                document.getElementById('popup-text').textContent = msg.text;
                
                popup.onclick = () => window.location.href = `index.php?page=inbox&friendId=${msg.senderId}`;
                
                popup.classList.add('show');
                
                // Hide after 5s
                setTimeout(() => {
                    popup.classList.remove('show');
                }, 5000);
            }

            // Initial check
            checkUnread();
            setInterval(checkUnread, 2000);
        }

        function formatTime(ms) {
            return new Date(ms).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
        }
    </script>
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    <script>
      window.OneSignalDeferred = window.OneSignalDeferred || [];
      OneSignalDeferred.push(async function(OneSignal) {
        await OneSignal.init({
          appId: "76bd2ec6-6a59-4324-a4ea-d90de19ed3c5",
        });
        const user = JSON.parse(localStorage.getItem('gt_user'));
        if (user && user.id) {
            OneSignal.login(user.id);
        }
      });
    </script>
</head>
<body>
    <div id="root">
        <div id="msg-popup" class="msg-popup">
            <img id="popup-avatar" src="" alt="" class="popup-avatar">
            <div class="popup-content">
                <div id="popup-name" class="popup-name"></div>
                <div id="popup-text" class="popup-text"></div>
            </div>
        </div>

<!-- made by fuad-ismayil -->