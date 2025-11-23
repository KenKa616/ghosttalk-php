import React, { useState, useContext } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { login } from '../services/mockBackend';
import { AuthContext } from '../App';

export default function Login() {
  const [username, setUsername] = useState('');
  const [error, setError] = useState('');
  const { setUser } = useContext(AuthContext);
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    try {
      const user = await login(username);
      setUser(user);
      navigate('/');
    } catch (err: any) {
      setError(err.message || 'Login failed');
    }
  };

  return (
    <div className="page-container flex-center flex-col" style={{height: '100%'}}>
      <h1 style={{ fontSize: '32px', letterSpacing: '-1px', marginBottom: '40px' }}>GhostTalk.</h1>
      
      <form onSubmit={handleSubmit} style={{ width: '100%' }}>
        <input 
          type="text" 
          placeholder="Username" 
          value={username} 
          onChange={e => setUsername(e.target.value)}
          required
        />
        
        {error && <p style={{ color: '#ff4444', marginBottom: '12px', fontSize: '14px' }}>{error}</p>}
        
        <button type="submit" className="primary-btn">Log In</button>
      </form>

      <p style={{ marginTop: '20px', color: '#666' }}>
        Don't have an account? <Link to="/register" style={{ color: '#fff', textDecoration: 'underline' }}>Join</Link>
      </p>
    </div>
  );
}