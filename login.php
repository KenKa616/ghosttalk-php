<?php include 'header.php'; ?>

<div class="page-container flex-center flex-col" style="height: 100%">
    <h1 style="font-size: 32px; letter-spacing: -1px; margin-bottom: 40px">GhostTalk.</h1>
    
    <form id="loginForm" style="width: 100%">
        <label for="username" style="display: none">Username</label>
        <input type="text" id="username" placeholder="Username" aria-label="Username" required />
        <p id="error" style="color: #ff4444; margin-bottom: 12px; font-size: 14px; display: none"></p>
        <button type="submit" class="primary-btn">Log In</button>
    </form>

    <p style="margin-top: 20px; color: #666">
        Don't have an account? <a href="register.php" style="color: #fff; text-decoration: underline">Join</a>
    </p>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('username').value;
        const errorEl = document.getElementById('error');
        
        try {
            const user = await apiCall('auth.php?action=login', 'POST', { username });
            setSession(user);
            window.location.href = 'index.php';
        } catch (err) {
            errorEl.textContent = err.message;
            errorEl.style.display = 'block';
        }
    });
</script>

<?php include 'footer.php'; ?>
