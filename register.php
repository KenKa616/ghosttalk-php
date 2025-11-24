<?php include 'header.php'; ?>

<div class="page-container flex-col flex-center" style="padding-bottom: 20px; height: 100vh">
    <div style="width: 100%; max-width: 320px; text-align: center">
        <h1 style="font-size: 28px; margin-bottom: 8px">Join the Shadows</h1>
        <p class="text-muted mb-4">Invite only. No traces.</p>
        
        <form id="registerForm" style="width: 100%; margin-top: 32px">
            <label for="username" class="sr-only">Username</label>
            <input type="text" id="username" placeholder="Username" aria-label="Username" required />
            <label for="inviteCode" class="sr-only">Invite Code</label>
            <input type="text" id="inviteCode" placeholder="Invite Code" aria-label="Invite Code" required />
            
            <p id="error" style="color: var(--danger-color); margin-bottom: 16px; font-size: 14px; display: none"></p>
            <button type="submit" class="primary-btn">Create Account</button>
        </form>

        <div class="mt-4">
            <a href="login.php" style="color: var(--text-muted); font-size: 14px">Already ghosting? <span style="color: var(--primary-color)">Log In</span></a>
        </div>
    </div>
</div>

<script>
    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('username').value;
        const inviteCode = document.getElementById('inviteCode').value;
        const errorEl = document.getElementById('error');
        
        try {
            const user = await apiCall('auth.php?action=register', 'POST', { username, inviteCode });
            setSession(user);
            window.location.href = 'index.php';
        } catch (err) {
            errorEl.textContent = err.message;
            errorEl.style.display = 'block';
        }
    });
</script>

<?php include 'footer.php'; ?>
