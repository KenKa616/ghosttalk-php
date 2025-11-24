<?php include 'header.php'; ?>

<div class="page-container flex-col flex-center" style="padding-bottom: 20px; height: 100vh">
    <div style="width: 100%; max-width: 320px; text-align: center">
        <div style="font-size: 48px; margin-bottom: 10px">👻</div>
        <h1 style="font-size: 32px; margin-bottom: 8px; background: linear-gradient(to right, #fff, #999); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">GhostTalk</h1>
        <p class="text-muted mb-4">Enter the void.</p>
        
        <form id="loginForm" style="width: 100%; margin-top: 32px">
            <label for="username" class="sr-only">Username</label>
            <input type="text" id="username" placeholder="Username" aria-label="Username" required />
            <p id="error" style="color: var(--danger-color); margin-bottom: 16px; font-size: 14px; display: none"></p>
            <button type="submit" class="primary-btn">Log In</button>
        </form>

        <div class="mt-4">
            <a href="register.php" style="color: var(--text-muted); font-size: 14px">Need an invite? <span style="color: var(--primary-color)">Join</span></a>
        </div>
    </div>
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

<!-- made by fuad-ismayil -->