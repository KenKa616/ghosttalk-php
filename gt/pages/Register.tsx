import React, { useState, useContext } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { register } from '../services/mockBackend';
import { AuthContext } from '../App';

export default function Register() {
  const [username, setUsername] = useState('');
  const [code, setCode] = useState('');
  const [error, setError] = useState('');
  const { setUser } = useContext(AuthContext);
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    try {
      const user = await register(username, code);
      setUser(user);
      navigate('/');
    } catch (err: any) {
      setError(err.message || 'Registration failed');
    }
  };

  return (
    <div className="page-container flex-center flex-col" style={{height: '100%'}}>
      <h1 style={{ fontSize: '32px', letterSpacing: '-1px', marginBottom: '10px' }}>Join GhostTalk.</h1>
      <p style={{ color: '#666', marginBottom: '30px', textAlign: 'center' }}>
        GhostTalk is invite-only. Enter your code below.
        <br/><small>(Use "GHOST" or "616" for testing)</small>
      </p>
      
      <form onSubmit={handleSubmit} style={{ width: '100%' }}>
        <input 
          type="text" 
          placeholder="Username" 
          value={username} 
          onChange={e => setUsername(e.target.value)}
          required
        />
        <input 
          type="text" 
          placeholder="Invite Code" 
          value={code} 
          onChange={e => setCode(e.target.value)}
          required
        />
        
        {error && <p style={{ color: '#ff4444', marginBottom: '12px', fontSize: '14px' }}>{error}</p>}
        
        <button type="submit" className="primary-btn">Create Account</button>
      </form>

      <p style={{ marginTop: '20px', color: '#666' }}>
        Already have an account? <Link to="/login" style={{ color: '#fff', textDecoration: 'underline' }}>Login</Link>
      </p>
    </div>
  );
}