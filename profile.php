<?php include 'header.php'; ?>

<div class="page-container flex-col flex-center">
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
</script>

<?php include 'footer.php'; ?>
