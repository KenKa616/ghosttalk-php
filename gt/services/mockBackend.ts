import { User, Post, ChatMessage } from '../types';

// Keys for LocalStorage
const USERS_KEY = 'gt_users';
const POSTS_KEY = 'gt_posts';
const CHATS_KEY = 'gt_chats';
const CURR_USER_KEY = 'gt_current_user';

// Utilities
const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms));
const generateId = () => Math.random().toString(36).substr(2, 9);

// --- AUTH SERVICES ---

export const getSession = (): User | null => {
  const stored = localStorage.getItem(CURR_USER_KEY);
  return stored ? JSON.parse(stored) : null;
};

export const login = async (username: string): Promise<User> => {
  await delay(500); // Simulate network
  const users: User[] = JSON.parse(localStorage.getItem(USERS_KEY) || '[]');
  const user = users.find(u => u.username.toLowerCase() === username.toLowerCase());
  
  if (!user) throw new Error('User not found.');
  
  localStorage.setItem(CURR_USER_KEY, JSON.stringify(user));
  return user;
};

export const register = async (username: string, inviteCode: string): Promise<User> => {
  await delay(500);
  const users: User[] = JSON.parse(localStorage.getItem(USERS_KEY) || '[]');
  
  // Check if username taken
  if (users.find(u => u.username.toLowerCase() === username.toLowerCase())) {
    throw new Error('Username taken.');
  }

  // Validate Invite Code
  // Allow 'GHOST' and '616' as test codes
  const validCode = inviteCode === 'GHOST' || inviteCode === '616' || users.some(u => u.inviteCode === inviteCode);
  if (!validCode) throw new Error('Invalid invite code. You cannot join without one.');

  // Create User
  const newUser: User = {
    id: generateId(),
    username,
    avatar: `https://api.dicebear.com/7.x/avataaars/svg?seed=${username}`, // Auto avatar
    inviteCode: generateId().toUpperCase().substring(0, 6),
    friends: []
  };

  // Add friend connection if invite code was from a user
  const inviter = users.find(u => u.inviteCode === inviteCode);
  if (inviter) {
    inviter.friends.push(newUser.id);
    newUser.friends.push(inviter.id);
  }

  users.push(newUser);
  localStorage.setItem(USERS_KEY, JSON.stringify(users));
  localStorage.setItem(CURR_USER_KEY, JSON.stringify(newUser));
  
  return newUser;
};

export const logout = () => {
  localStorage.removeItem(CURR_USER_KEY);
};

export const updateAvatar = async (avatarUrl: string): Promise<User> => {
  await delay(300);
  const currentUser = getSession();
  if (!currentUser) throw new Error('Unauthorized');

  const users: User[] = JSON.parse(localStorage.getItem(USERS_KEY) || '[]');
  const userIndex = users.findIndex(u => u.id === currentUser.id);

  if (userIndex === -1) throw new Error('User not found');

  // Update user
  users[userIndex].avatar = avatarUrl;
  
  // Update storage
  localStorage.setItem(USERS_KEY, JSON.stringify(users));
  localStorage.setItem(CURR_USER_KEY, JSON.stringify(users[userIndex]));

  // Update active posts by this user to show new avatar immediately
  const posts: Post[] = JSON.parse(localStorage.getItem(POSTS_KEY) || '[]');
  let postsChanged = false;
  const updatedPosts = posts.map(p => {
    if (p.userId === currentUser.id) {
      postsChanged = true;
      return { ...p, userAvatar: avatarUrl };
    }
    return p;
  });

  if (postsChanged) {
    localStorage.setItem(POSTS_KEY, JSON.stringify(updatedPosts));
  }

  return users[userIndex];
};

// --- POST SERVICES ---

export const createPost = async (imageUrl: string): Promise<Post> => {
  await delay(300);
  const currentUser = getSession();
  if (!currentUser) throw new Error('Unauthorized');

  const posts: Post[] = JSON.parse(localStorage.getItem(POSTS_KEY) || '[]');
  
  const newPost: Post = {
    id: generateId(),
    userId: currentUser.id,
    username: currentUser.username,
    userAvatar: currentUser.avatar,
    imageUrl,
    createdAt: Date.now(),
    expiresAt: Date.now() + 30 * 1000 // 30 Seconds expiry
  };

  posts.unshift(newPost);
  localStorage.setItem(POSTS_KEY, JSON.stringify(posts));
  return newPost;
};

export const getFeed = async (): Promise<Post[]> => {
  await delay(300);
  const currentUser = getSession();
  if (!currentUser) return [];

  const posts: Post[] = JSON.parse(localStorage.getItem(POSTS_KEY) || '[]');
  const now = Date.now();

  // Filter expired posts and posts only from self or friends
  const validPosts = posts.filter(p => {
    const isExpired = now > p.expiresAt;
    const isFriend = currentUser.friends.includes(p.userId);
    const isSelf = p.userId === currentUser.id;
    return !isExpired && (isFriend || isSelf);
  });

  // Clean up storage (remove expired)
  const unexpiredPosts = posts.filter(p => now <= p.expiresAt);
  if (unexpiredPosts.length !== posts.length) {
    localStorage.setItem(POSTS_KEY, JSON.stringify(unexpiredPosts));
  }

  return validPosts;
};

// --- USER SERVICES ---

export const searchUsers = async (query: string): Promise<User[]> => {
  await delay(200);
  const users: User[] = JSON.parse(localStorage.getItem(USERS_KEY) || '[]');
  const currentUser = getSession();
  
  return users.filter(u => 
    u.id !== currentUser?.id && 
    u.username.toLowerCase().includes(query.toLowerCase())
  );
};

export const addFriend = async (friendId: string) => {
  const currentUser = getSession();
  if (!currentUser) return;
  const users: User[] = JSON.parse(localStorage.getItem(USERS_KEY) || '[]');
  
  const userIdx = users.findIndex(u => u.id === currentUser.id);
  const friendIdx = users.findIndex(u => u.id === friendId);

  if (userIdx > -1 && friendIdx > -1) {
    // Simple mutual add for prototype
    if (!users[userIdx].friends.includes(friendId)) users[userIdx].friends.push(friendId);
    if (!users[friendIdx].friends.includes(currentUser.id)) users[friendIdx].friends.push(currentUser.id);
    
    localStorage.setItem(USERS_KEY, JSON.stringify(users));
    localStorage.setItem(CURR_USER_KEY, JSON.stringify(users[userIdx]));
  }
};

// --- CHAT SERVICES ---

export const sendMessage = async (receiverId: string, text: string) => {
  const currentUser = getSession();
  if (!currentUser) return;

  const chats: ChatMessage[] = JSON.parse(localStorage.getItem(CHATS_KEY) || '[]');
  const newMessage: ChatMessage = {
    id: generateId(),
    senderId: currentUser.id,
    receiverId,
    text,
    timestamp: Date.now()
  };
  
  chats.push(newMessage);
  localStorage.setItem(CHATS_KEY, JSON.stringify(chats));
};

export const getMessages = async (friendId: string): Promise<ChatMessage[]> => {
  const currentUser = getSession();
  if (!currentUser) return [];

  const chats: ChatMessage[] = JSON.parse(localStorage.getItem(CHATS_KEY) || '[]');
  return chats.filter(c => 
    (c.senderId === currentUser.id && c.receiverId === friendId) ||
    (c.senderId === friendId && c.receiverId === currentUser.id)
  ).sort((a, b) => a.timestamp - b.timestamp);
};

export const getChatSessions = async (): Promise<User[]> => {
  const currentUser = getSession();
  if (!currentUser) return [];
  
  const users: User[] = JSON.parse(localStorage.getItem(USERS_KEY) || '[]');
  // Return only friends
  return users.filter(u => currentUser.friends.includes(u.id));
};