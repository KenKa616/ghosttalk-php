export interface User {
  id: string;
  username: string;
  avatar: string; // URL or base64 placeholder
  inviteCode: string; // The code this user can share
  friends: string[]; // Array of user IDs
}

export interface Post {
  id: string;
  userId: string;
  username: string;
  userAvatar: string;
  imageUrl: string;
  createdAt: number; // Timestamp
  expiresAt: number; // Timestamp
}

export interface ChatMessage {
  id: string;
  senderId: string;
  receiverId: string;
  text: string;
  timestamp: number;
}

export interface ChatSession {
  userId: string;
  username: string;
  avatar: string;
  lastMessage: string;
}