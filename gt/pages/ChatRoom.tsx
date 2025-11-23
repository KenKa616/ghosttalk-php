import React, { useEffect, useState, useRef, useContext } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { getMessages, sendMessage, getSession } from '../services/mockBackend';
import { ChatMessage } from '../types';
import { AuthContext } from '../App';

export default function ChatRoom() {
  const { friendId } = useParams();
  const navigate = useNavigate();
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [text, setText] = useState('');
  const { user } = useContext(AuthContext);
  const bottomRef = useRef<HTMLDivElement>(null);

  const loadMessages = async () => {
    if (friendId) {
      const msgs = await getMessages(friendId);
      setMessages(msgs);
    }
  };

  useEffect(() => {
    loadMessages();
    const interval = setInterval(loadMessages, 2000); // Polling for new messages
    return () => clearInterval(interval);
  }, [friendId]);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!text.trim() || !friendId) return;
    await sendMessage(friendId, text);
    setText('');
    loadMessages();
  };

  return (
    <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
      <div className="header" style={{ justifyContent: 'flex-start', paddingLeft: '15px' }}>
        <button onClick={() => navigate('/chat')} style={{ background: 'none', color: '#fff', marginRight: '10px', fontSize: '20px' }}>←</button>
        Chat
      </div>
      
      <div className="page-container" style={{ display: 'flex', flexDirection: 'column', paddingBottom: '0' }}>
        <div style={{ flex: 1, display: 'flex', flexDirection: 'column' }}>
          {messages.map(msg => (
            <div 
              key={msg.id} 
              className={`message-bubble ${msg.senderId === user?.id ? 'msg-own' : 'msg-other'}`}
            >
              {msg.text}
            </div>
          ))}
          <div ref={bottomRef} />
        </div>
      </div>

      <form 
        onSubmit={handleSend}
        style={{ 
          padding: '10px', 
          background: '#000', 
          borderTop: '1px solid #222', 
          display: 'flex',
          gap: '10px'
        }}
      >
        <input 
          type="text" 
          value={text} 
          onChange={e => setText(e.target.value)}
          placeholder="Message..."
          style={{ marginBottom: 0, borderRadius: '20px' }}
        />
        <button type="submit" style={{ color: '#fff', background: 'none', fontWeight: 'bold' }}>Send</button>
      </form>
    </div>
  );
}