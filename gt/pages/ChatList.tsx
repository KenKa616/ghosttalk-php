import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { getChatSessions } from '../services/mockBackend';
import { User } from '../types';

export default function ChatList() {
  const [friends, setFriends] = useState<User[]>([]);

  useEffect(() => {
    getChatSessions().then(setFriends);
  }, []);

  return (
    <>
      <header className="header">Messages</header>
      <div className="page-container" style={{ padding: 0 }}>
        {friends.length === 0 && (
          <div style={{ padding: '20px', color: '#666', textAlign: 'center' }}>
            No friends yet. Go to Search to find people.
          </div>
        )}
        {friends.map(friend => (
          <Link to={`/chat/${friend.id}`} key={friend.id} className="chat-list-item">
            <img src={friend.avatar} alt="av" className="avatar" style={{ width: '48px', height: '48px' }} />
            <div>
              <div style={{ fontWeight: 600, fontSize: '16px', color: '#fff' }}>{friend.username}</div>
              <div style={{ fontSize: '14px', color: '#666' }}>Tap to chat</div>
            </div>
          </Link>
        ))}
      </div>
    </>
  );
}