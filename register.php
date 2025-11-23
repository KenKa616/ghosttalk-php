<?php include 'header.php'; ?>

<div class="page-container flex-center flex-col" style="height: 100%">
    <h1 style="font-size: 32px; letter-spacing: -1px; margin-bottom: 10px">Join GhostTalk.</h1>
    <p style="color: #666; margin-bottom: 30px; text-align: center">
        GhostTalk is invite-only. Enter your code below.
        <br/><small>(Use "GHOST" or "616" for testing)</small>
    </p>
    
    <form id="registerForm" style="width: 100%">
        <label for="username" style="display: none">Username</label>
        <input type="text" id="username" placeholder="Username" aria-label="Username" required />
        <label for="inviteCode" style="display: none">Invite Code</label>
        <input type="text" id="inviteCode" placeholder="Invite Code" aria-label="Invite Code" required />
        
        <p id="error" style="color: #ff4444; margin-bottom: 12px; font-size: 14px; display: none"></p>
        
        <button type="submit" class="primary-btn">Create Account</button>
    </form>

    <p style="margin-top: 20px; color: #666">
        Already have an account? <a href="login.php" style="color: #fff; text-decoration: underline">Login</a>
    </p>
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
