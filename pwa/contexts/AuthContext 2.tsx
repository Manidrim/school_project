import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
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

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const router = useRouter();

  const checkAuth = async () => {
    try {
      const response = await fetch('http://localhost/admin', {
        credentials: 'include',
      });

      if (response.ok) {
        const text = await response.text();
        const emailMatch = text.match(/Welcome, ([^!]+)!/);
        if (emailMatch) {
          setUser({
            email: emailMatch[1],
            roles: ['ROLE_ADMIN']
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
      
      const response = await fetch('http://localhost/login', {
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
      await fetch('http://localhost/logout', {
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
      const response = await fetch('http://localhost/login', {
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