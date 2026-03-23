import React, { createContext, useContext, useState, useEffect } from 'react';
import { useRouter } from 'next/router';

interface User {
  email: string;
  roles: string[];
}

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<boolean>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

interface AuthProviderProps {
  children: React.ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const router = useRouter();

  const checkAuth = async () => {
    try {
      const response = await fetch('http://localhost:8080/api/admin', {
        credentials: 'include',
      });

      if (response.ok) {
        const data = await response.json();
        if (data.title === 'Admin Dashboard' && data.user) {
          setUser({
            email: data.user.email,
            roles: data.user.roles
          });
        }
      } else {
        setUser(null);
      }
    } catch (error) {
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (email: string, password: string): Promise<boolean> => {
    try {
      const csrfToken = await getCsrfToken();
      
      const response = await fetch('https://localhost/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'include',
        body: new URLSearchParams({
          email,
          password,
          _csrf_token: csrfToken,
        }),
      });

      if (response.ok) {
        setUser({
          email,
          roles: ['ROLE_ADMIN']
        });
        return true;
      }
      return false;
    } catch (error) {
      return false;
    }
  };

  const logout = async (): Promise<void> => {
    try {
      await fetch('https://localhost/logout', {
        method: 'GET',
        credentials: 'include',
      });
    } catch (error) {
      console.error('Logout error:', error);
    }
    
    setUser(null);
    router.push('/login');
  };

  const getCsrfToken = async (): Promise<string> => {
    try {
      const response = await fetch('https://localhost/login', {
        credentials: 'include',
      });
      const html = await response.text();
      const match = html.match(/name="_csrf_token"\s+value="([^"]+)"/);
      return match ? match[1] : '';
    } catch {
      return '';
    }
  };

  useEffect(() => {
    checkAuth();
  }, []);

  const value = {
    user,
    loading,
    login,
    logout,
    checkAuth,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}; 