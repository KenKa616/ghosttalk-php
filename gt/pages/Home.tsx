import React, { useEffect, useState, useRef } from 'react';
import { getFeed, createPost } from '../services/mockBackend';
import { Post } from '../types';

export default function Home() {
  const [posts, setPosts] = useState<Post[]>([]);
  const [uploading, setUploading] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const refreshFeed = async () => {
    const data = await getFeed();
    setPosts(data);
  };

  useEffect(() => {
    refreshFeed();
    // Refresh every second to update countdowns and remove expired
    const interval = setInterval(() => {
      // We do a client-side filter first to avoid flickering
      setPosts(prev => prev.filter(p => Date.now() < p.expiresAt));
    }, 1000);
    return () => clearInterval(interval);
  }, []);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setUploading(true);
    const reader = new FileReader();
    reader.onloadend = async () => {
      if (typeof reader.result === 'string') {
        await createPost(reader.result);
        setUploading(false);
        refreshFeed();
      }
    };
    reader.readAsDataURL(file);
  };

  return (
    <>
      <header className="header">GhostTalk</header>
      <div className="page-container">
        {posts.length === 0 && !uploading && (
          <div className="flex-center flex-col" style={{ height: '60vh', color: '#444' }}>
            <p>No ghosts here.</p>
            <p style={{ fontSize: '12px' }}>Tap + to post a 30s photo.</p>
          </div>
        )}

        {posts.map(post => (
          <PostItem key={post.id} post={post} />
        ))}
      </div>

      {/* Upload FAB */}
      <button 
        className="fab" 
        onClick={() => fileInputRef.current?.click()}
        disabled={uploading}
      >
        {uploading ? "..." : "+"}
      </button>
      <input 
        type="file" 
        ref={fileInputRef} 
        style={{ display: 'none' }} 
        accept="image/*" 
        onChange={handleFileChange}
      />
    </>
  );
}

// Separate component for complex interactions
const PostItem: React.FC<{ post: Post }> = ({ post }) => {
  const [revealed, setRevealed] = useState(false);
  const [timeLeft, setTimeLeft] = useState(30);

  useEffect(() => {
    const updateTimer = () => {
      const remaining = Math.ceil((post.expiresAt - Date.now()) / 1000);
      setTimeLeft(remaining);
    };
    updateTimer();
    const timer = setInterval(updateTimer, 1000);
    return () => clearInterval(timer);
  }, [post.expiresAt]);

  // Disable context menu on this specific element
  const handleContextMenu = (e: React.MouseEvent) => e.preventDefault();

  return (
    <div className="post-card" onContextMenu={handleContextMenu}>
      <div className="post-header">
        <img src={post.userAvatar} alt="av" className="avatar" />
        <span style={{ fontWeight: 600, fontSize: '14px' }}>{post.username}</span>
        <div className="countdown-timer">{timeLeft}s</div>
      </div>

      <div 
        className="post-image-container"
        onMouseDown={() => setRevealed(true)}
        onMouseUp={() => setRevealed(false)}
        onMouseLeave={() => setRevealed(false)}
        onTouchStart={() => setRevealed(true)}
        onTouchEnd={() => setRevealed(false)}
      >
        <img 
          src={post.imageUrl} 
          alt="Ghost Content" 
          className={`post-image ${revealed ? 'revealed' : ''}`}
          draggable={false}
        />
        
        {!revealed && <div className="hold-hint">Hold to View</div>}
        
        {revealed && (
            <div className="watermark">
                GHOSTTALK • {post.username} • GHOSTTALK
            </div>
        )}
      </div>
    </div>
  );
};