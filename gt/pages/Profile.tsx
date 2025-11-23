import React, { useContext, useRef, useState } from 'react';
import { AuthContext } from '../App';
import { logout, updateAvatar } from '../services/mockBackend';

export default function Profile() {
  const { user, setUser } = useContext(AuthContext);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [loading, setLoading] = useState(false);

  const handleLogout = () => {
    logout();
    setUser(null);
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setLoading(true);
    const reader = new FileReader();
    reader.onloadend = async () => {
      if (typeof reader.result === 'string') {
        try {
          const updatedUser = await updateAvatar(reader.result);
          setUser(updatedUser);
        } catch (err) {
          console.error("Failed to update avatar", err);
        } finally {
          setLoading(false);
        }
      }
    };
    reader.readAsDataURL(file);
  };

  if (!user) return null;

  return (
    <>
      <header className="header">Profile</header>
      <div className="page-container flex-center flex-col">
        
        {/* Profile Picture Upload Section */}
        <div 
          className="avatar-wrapper" 
          onClick={() => !loading && fileInputRef.current?.click()}
          title="Click to change permanent profile picture"
        >
          <img 
            src={user.avatar} 
            alt="Profile" 
            className="profile-avatar-large"
          />
          <div className="avatar-overlay">
             {loading ? '...' : 'Edit'}
          </div>
        </div>
        <input 
          type="file" 
          ref={fileInputRef}
          accept="image/*"
          style={{ display: 'none' }}
          onChange={handleFileChange}
        />

        <h2 style={{ fontSize: '24px', marginBottom: '5px' }}>{user.username}</h2>
        
        <div style={{ 
          background: '#111', 
          padding: '20px', 
          borderRadius: '12px', 
          width: '100%', 
          marginTop: '30px',
          border: '1px solid #222'
        }}>
          <h3 style={{ fontSize: '14px', color: '#666', marginBottom: '8px', textTransform: 'uppercase' }}>Your Invite Code</h3>
          <div style={{ fontSize: '28px', fontFamily: 'monospace', letterSpacing: '2px', textAlign: 'center' }}>
            {user.inviteCode}
          </div>
          <p style={{ fontSize: '12px', color: '#444', textAlign: 'center', marginTop: '10px' }}>
            Share this code to invite friends.
          </p>
        </div>

        <div style={{ marginTop: '20px', width: '100%', display: 'flex', gap: '10px' }}>
            <div style={{ flex: 1, background: '#111', padding: '15px', borderRadius: '12px', textAlign: 'center', border: '1px solid #222' }}>
                <div style={{fontSize: '20px', fontWeight: 'bold'}}>{user.friends.length}</div>
                <div style={{color: '#666', fontSize: '12px'}}>Friends</div>
            </div>
        </div>

        <button 
          onClick={handleLogout}
          style={{ 
            marginTop: '40px', 
            background: '#111', 
            color: '#ff4444', 
            padding: '15px', 
            borderRadius: '12px', 
            width: '100%',
            border: '1px solid #222' 
          }}
        >
          Log Out
        </button>
      </div>
    </>
  );
}