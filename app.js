var API_URL = '/api';

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

    const res = await fetch(`${API_URL}/${endpoint}`, options);
    if (!res.ok) {
        const err = await res.json();
        throw new Error(err.error || 'API Error');
    }
    return res.json();
}

// --- UI UTILS ---
function renderNav(activePage) {
    const nav = document.createElement('div');
    nav.className = 'bottom-nav';

    const icons = {
        home: (active) => `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="${active ? "#fff" : "none"}" stroke="${active ? "#fff" : "#666"}" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            </svg>`,
        search: (active) => `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="${active ? "#fff" : "#666"}" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>`,
        chat: (active) => `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="${active ? "#fff" : "none"}" stroke="${active ? "#fff" : "#666"}" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>`,
        profile: (active) => `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="${active ? "#fff" : "none"}" stroke="${active ? "#fff" : "#666"}" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>`
    };

    nav.innerHTML = `
        <a href="index.php" class="nav-item ${activePage === 'home' ? 'active' : ''}" aria-label="Home">
            ${icons.home(activePage === 'home')}
        </a>
        <a href="search.php" class="nav-item ${activePage === 'search' ? 'active' : ''}" aria-label="Search">
            ${icons.search(activePage === 'search')}
        </a>
        <a href="chat.php" class="nav-item ${activePage === 'chat' ? 'active' : ''}" style="position: relative" aria-label="Chat">
            ${icons.chat(activePage === 'chat')}
            <div id="unread-dot" style="position: absolute; top: 10px; right: 20px; width: 8px; height: 8px; background: #fff; border-radius: 50%; display: none"></div>
        </a>
        <a href="profile.php" class="nav-item ${activePage === 'profile' ? 'active' : ''}" aria-label="Profile">
            ${icons.profile(activePage === 'profile')}
        </a>
    `;
    document.getElementById('root').appendChild(nav);

    // Check unread
    if (getSession()) {
        apiCall(`chat.php?action=unread&userId=${getSession().id}`).then(res => {
            if (res.count > 0) {
                document.getElementById('unread-dot').style.display = 'block';
            }
        }).catch(() => { });
    }
}

function formatTime(ms) {
    return new Date(ms).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
