import React from 'react'
import { render } from '@testing-library/react'
import { AuthContext } from '../contexts/AuthContext'

interface User {
  email: string
  roles: string[]
}

interface AuthContextType {
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<boolean>
  logout: () => Promise<void>
  checkAuth: () => Promise<void>
}

interface MockAuthProviderProps {
  children: React.ReactNode
  value: Partial<AuthContextType>
}

export const MockAuthProvider: React.FC<MockAuthProviderProps> = ({ 
  children, 
  value 
}) => {
  const defaultValue: AuthContextType = {
    user: null,
    loading: false,
    login: jest.fn().mockResolvedValue(false),
    logout: jest.fn().mockResolvedValue(undefined),
    checkAuth: jest.fn().mockResolvedValue(undefined),
    ...value,
  }

  return (
    <AuthContext.Provider value={defaultValue}>
      {children}
    </AuthContext.Provider>
  )
}

export const renderWithMockAuth = (
  ui: React.ReactElement,
  authValue: Partial<AuthContextType> = {}
) => {
  return render(
    <MockAuthProvider value={authValue}>
      {ui}
    </MockAuthProvider>
  )
}

export const createMockUser = (overrides: Partial<User> = {}): User => ({
  email: 'test@example.com',
  roles: ['ROLE_ADMIN'],
  ...overrides,
})

// Dummy test to satisfy Jest requirement
describe('Test Utils', () => {
  it('should create mock user correctly', () => {
    const user = createMockUser({ email: 'custom@test.com' })
    expect(user.email).toBe('custom@test.com')
    expect(user.roles).toEqual(['ROLE_ADMIN'])
  })
})