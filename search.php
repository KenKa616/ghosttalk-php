<?php include 'header.php'; ?>

<!-- Custom Header for Search -->
<div class="header">
    <label for="searchInput" class="sr-only">Search users</label>
    <input type="text" id="searchInput" placeholder="Search users..." aria-label="Search users" style="width: 90%; margin: 0; margin-top: 10px; background: var(--surface-light); border: none; border-radius: 20px" autofocus />
</div>

<div class="page-container">
    <div id="results-container">
        <!-- Results injected here -->
    </div>
    <p id="no-results" style="color: #444; text-align: center; margin-top: 20px; display: none">No users found.</p>
</div>

<script>
    const user = requireAuth();
    renderNav('search');

    const searchInput = document.getElementById('searchInput');
    const container = document.getElementById('results-container');
    const noResults = document.getElementById('no-results');

    searchInput.addEventListener('input', async (e) => {
        const query = e.target.value;
        if (query.length < 2) {
            container.innerHTML = '';
            noResults.style.display = 'none';
            return;
        }

        try {
            const results = await apiCall(`users.php?action=search&q=${query}&userId=${user.id}`);
            
            if (results.length === 0) {
                container.innerHTML = '';
                noResults.style.display = 'block';
            } else {
                noResults.style.display = 'none';
                container.innerHTML = results.map(u => `
                    <div class="flex-between" style="padding: 15px 0; border-bottom: 1px solid #111">
                        <div class="flex-center" style="gap: 10px">
                            <img src="${u.avatar}" alt="av" class="avatar" />
                            <span>${u.username}</span>
                        </div>
                        <button onclick="addFriend('${u.id}')" style="padding: 6px 12px; background: #fff; color: #000; border-radius: 6px; font-weight: bold; font-size: 12px">
                            Add
                        </button>
                    </div>
                `).join('');
            }
        } catch (err) {
            console.error(err);
        }
    });

    async function addFriend(friendId) {
        try {
            await apiCall('users.php?action=friend', 'POST', { userId: user.id, friendId });
            alert('Friend added!');
            // Refresh search to potentially remove added friend or update UI (optional)
        } catch (err) {
            alert('Failed to add friend');
        }
    }
</script>

<?php include 'footer.php'; ?>

<!-- made by fuad-ismayil -->