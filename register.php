<?php include 'header.php'; ?>
<?php include 'header.php'; ?>

<div class="page-container flex-col flex-center" style="padding-bottom: 20px; height: 100vh">
    <div style="width: 100%; max-width: 320px; text-align: center">
        <h1 style="font-size: 28px; margin-bottom: 8px">Join the Shadows</h1>
        <p class="text-muted mb-4">Invite only. No traces.</p>
        
        <form id="registerForm" style="width: 100%; margin-top: 32px">
            <div style="position: relative; margin-bottom: 12px">
                <label for="username" class="sr-only">Username</label>
                <input type="text" id="username" placeholder="Username" aria-label="Username" required style="margin-bottom: 0; padding-right: 40px" autocomplete="off" />
                <div id="username-indicator" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-weight: bold; display: none"></div>
            </div>
            
            <label for="password" class="sr-only">Password</label>
            <input type="password" id="password" placeholder="Password" aria-label="Password" required style="margin-bottom: 12px" />
            
            <label for="repeatPassword" class="sr-only">Repeat Password</label>
            <input type="password" id="repeatPassword" placeholder="Repeat Password" aria-label="Repeat Password" required />

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
    const usernameInput = document.getElementById('username');
    const indicator = document.getElementById('username-indicator');

    usernameInput.addEventListener('input', async (e) => {
        const username = e.target.value.trim();
        if (username.length < 3) {
            indicator.style.display = 'none';
            return;
        }

        try {
            const res = await apiCall(`auth.php?action=check_username&username=${encodeURIComponent(username)}`);
            indicator.style.display = 'block';
            if (res.available) {
                indicator.textContent = '✓';
                indicator.style.color = '#4ade80'; // Green
            } else {
                indicator.textContent = '✕';
                indicator.style.color = '#ef4444'; // Red
            }
        } catch (err) {
            console.error(err);
        }
    });

    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const repeatPassword = document.getElementById('repeatPassword').value;
        const inviteCode = document.getElementById('inviteCode').value;
        const errorEl = document.getElementById('error');
        
        if (password !== repeatPassword) {
            errorEl.textContent = "Passwords do not match";
            errorEl.style.display = 'block';
            return;
        }

        try {
            const user = await apiCall('auth.php?action=register', 'POST', { username, password, inviteCode });
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