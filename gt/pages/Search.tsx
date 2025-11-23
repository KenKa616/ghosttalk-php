import React, { useState } from 'react';
import { searchUsers, addFriend } from '../services/mockBackend';
import { User } from '../types';

export default function Search() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<User[]>([]);

  const handleSearch = async (val: string) => {
    setQuery(val);
    if (val.length > 1) {
      const res = await searchUsers(val);
      setResults(res);
    } else {
      setResults([]);
    }
  };

  const handleAdd = async (id: string) => {
    await addFriend(id);
    alert('Friend added!');
    handleSearch(query); // Refresh
  };

  return (
    <>
      <div className="header">
        <input 
          type="text" 
          placeholder="Search users..." 
          value={query}
          onChange={(e) => handleSearch(e.target.value)}
          style={{ width: '90%', margin: 0, background: '#222', border: 'none' }}
          autoFocus
        />
      </div>
      <div className="page-container">
        {results.map(user => (
          <div key={user.id} className="flex-between" style={{ padding: '15px 0', borderBottom: '1px solid #111' }}>
            <div className="flex-center" style={{ gap: '10px' }}>
              <img src={user.avatar} alt="av" className="avatar" />
              <span>{user.username}</span>
            </div>
            <button 
              onClick={() => handleAdd(user.id)}
              style={{ padding: '6px 12px', background: '#fff', color: '#000', borderRadius: '6px', fontWeight: 'bold', fontSize: '12px' }}
            >
              Add
            </button>
          </div>
        ))}
        {query && results.length === 0 && <p style={{ color: '#444', textAlign: 'center', marginTop: '20px' }}>No users found.</p>}
      </div>
    </>
  );
}