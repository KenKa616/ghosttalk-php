<?php include 'header.php'; ?>

<div class="header">
    Feed
</div>

<div class="page-container" id="feed-container">
    <!-- Posts injected here -->
</div>

<div id="empty-state" class="flex-center flex-col" style="height: 60vh; color: var(--text-muted); display: none;">
    <p>No ghosts here.</p>
    <p style="font-size: 12px">Tap + to post a 30s photo.</p>
</div>

<!-- Upload FAB -->
<button class="fab" onclick="document.getElementById('fileInput').click()">
    +
</button>
<label for="fileInput" class="sr-only">Upload Image</label>
<input type="file" id="fileInput" style="display: none" accept="image/*" aria-label="Upload Image" onchange="handleFileChange(this)" />

<script>
    const user = requireAuth();
    renderNav('home');

    async function loadFeed() {
        try {
            const posts = await apiCall(`posts.php?userId=${user.id}`);
            const container = document.getElementById('feed-container');
            const emptyState = document.getElementById('empty-state');
            
            if (!container || !emptyState) return;

            // Client-side filter for expiry to avoid flicker
            const validPosts = posts.filter(p => Date.now() < p.expiresAt);

            if (validPosts.length === 0) {
                container.innerHTML = '';
                emptyState.style.display = 'flex';
                return;
            }

            emptyState.style.display = 'none';
            
            // Simple diffing: if count changed, re-render all (prototype shortcut)
            if (container.children.length !== validPosts.length) {
                container.innerHTML = validPosts.map(post => createPostHTML(post)).join('');
                attachPostEvents();
            } else {
                // Just update timers
                validPosts.forEach(post => {
                    const timerEl = document.getElementById(`timer-${post.id}`);
                    if (timerEl) {
                        const remaining = Math.ceil((post.expiresAt - Date.now()) / 1000);
                        timerEl.textContent = `${remaining}s`;
                    }
                });
            }
        } catch (err) {
            console.error(err);
        }
    }

    function createPostHTML(post) {
        const remaining = Math.ceil((post.expiresAt - Date.now()) / 1000);
        return `
            <div class="post-card" id="post-${post.id}">
                <div class="post-header">
                    <img src="${post.userAvatar}" alt="av" class="avatar" />
                    <span style="font-weight: 600; font-size: 14px">${post.username}</span>
                    <div class="countdown-timer" id="timer-${post.id}">${remaining}s</div>
                </div>

                <div class="post-image-container" 
                     onmousedown="reveal(this)" 
                     onmouseup="hide(this)" 
                     onmouseleave="hide(this)"
                     ontouchstart="reveal(this)" 
                     ontouchend="hide(this)">
                    <img src="${post.imageUrl}" alt="Ghost Content" class="post-image" draggable="false" />
                    <div class="hold-hint">Hold to View</div>
                    <div class="watermark" style="display: none">
                        GHOSTTALK • ${post.username} • GHOSTTALK
                    </div>
                </div>
            </div>
        `;
    }

    function reveal(el) {
        el.querySelector('.post-image').classList.add('revealed');
        el.querySelector('.hold-hint').style.display = 'none';
        el.querySelector('.watermark').style.display = 'block';
    }

    function hide(el) {
        el.querySelector('.post-image').classList.remove('revealed');
        el.querySelector('.hold-hint').style.display = 'block';
        el.querySelector('.watermark').style.display = 'none';
    }

    function attachPostEvents() {
        // Events are inline in HTML string for simplicity in vanilla JS
    }

    async function handleFileChange(input) {
        const file = input.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onloadend = async () => {
            if (typeof reader.result === 'string') {
                try {
                    await apiCall('posts.php', 'POST', { userId: user.id, imageUrl: reader.result });
                    loadFeed();
                } catch (err) {
                    alert('Failed to post');
                }
            }
        };
        reader.readAsDataURL(file);
    }

    // Initial load and polling
    loadFeed();
    setInterval(loadFeed, 1000);
</script>

<?php include 'footer.php'; ?>
