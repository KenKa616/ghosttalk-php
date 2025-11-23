<?php include 'header.php'; ?>

<div class="page-container flex-center flex-col">
    <!-- Profile Picture -->
    <div class="avatar-wrapper" onclick="document.getElementById('avatarInput').click()">
        <img id="profile-avatar" src="" alt="Profile" class="profile-avatar-large" />
        <div class="avatar-overlay">Edit</div>
    </div>
    <label for="avatarInput" style="display: none">Upload Avatar</label>
    <input type="file" id="avatarInput" style="display: none" accept="image/*" aria-label="Upload Avatar" onchange="handleAvatarChange(this)" />

    <h2 id="profile-username" style="font-size: 24px; margin-bottom: 5px"></h2>
    
    <div style="background: #111; padding: 20px; border-radius: 12px; width: 100%; margin-top: 30px; border: 1px solid #222">
        <h3 style="font-size: 14px; color: #666; margin-bottom: 8px; text-transform: uppercase">Your Invite Code</h3>
        <div id="invite-code" style="font-size: 28px; font-family: monospace; letter-spacing: 2px; text-align: center"></div>
        <p style="font-size: 12px; color: #444; text-align: center; margin-top: 10px">
            Share this code to invite friends.
        </p>
    </div>

    <div style="margin-top: 20px; width: 100%; display: flex; gap: 10px">
        <div style="flex: 1; background: #111; padding: 15px; border-radius: 12px; text-align: center; border: 1px solid #222">
            <div id="friend-count" style="font-size: 20px; font-weight: bold">0</div>
            <div style="color: #666; font-size: 12px">Friends</div>
        </div>
    </div>

    <button onclick="handleLogout()" style="margin-top: 40px; background: #111; color: #ff4444; padding: 15px; border-radius: 12px; width: 100%; border: 1px solid #222">
        Log Out
    </button>
</div>

<script>
    const user = requireAuth();
    renderNav('profile');

    // Render initial data
    document.getElementById('profile-avatar').src = user.avatar;
    document.getElementById('profile-username').textContent = user.username;
    document.getElementById('invite-code').textContent = user.inviteCode;
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
