import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AuthProvider, useAuth } from '../../contexts/AuthContext'

const TestComponent = () => {
  const { user, loading, login, logout, checkAuth } = useAuth()
  
  return (
    <div>
      {loading && <div data-testid="loading">Loading...</div>}
      {user ? (
        <div>
          <div data-testid="user-email">{user.email}</div>
          <button onClick={logout} data-testid="logout-button">Logout</button>
          <button onClick={checkAuth} data-testid="check-auth-button">Check Auth</button>
        </div>
      ) : (
        <div>
          <div data-testid="not-authenticated">Not authenticated</div>
          <button onClick={() => login('test@example.com', 'password')} data-testid="login-button">
            Login
          </button>
        </div>
      )}
    </div>
  )
}

const MockedAuthProvider = () => (
  <AuthProvider>
    <TestComponent />
  </AuthProvider>
)

describe('AuthContext Edge Cases', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    global.fetch = jest.fn()
  })

  it('handles checkAuth with successful response but no user data', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ title: 'Some Page' }),
    })

    render(<MockedAuthProvider />)
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('handles checkAuth with network error', async () => {
    ;(global.fetch as jest.Mock).mockRejectedValue(new Error('Network error'))

    render(<MockedAuthProvider />)
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('handles login with network error', async () => {
    const user = userEvent.setup()
    
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: false,
      })
      .mockRejectedValue(new Error('Network error'))

    render(<MockedAuthProvider />)
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
    
    const loginButton = screen.getByTestId('login-button')
    await user.click(loginButton)
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('handles getCsrfToken failure', async () => {
    const user = userEvent.setup()
    
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: false,
      })
      .mockRejectedValue(new Error('CSRF error'))

    render(<MockedAuthProvider />)
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
    
    const loginButton = screen.getByTestId('login-button')
    await user.click(loginButton)
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('handles logout with network error gracefully', async () => {
    const user = userEvent.setup()
    
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          title: 'Admin Dashboard',
          user: { email: 'test@example.com', roles: ['ROLE_ADMIN'] }
        }),
      })
      .mockRejectedValue(new Error('Logout error'))

    render(<MockedAuthProvider />)
    
    await waitFor(() => {
      expect(screen.getByTestId('user-email')).toBeInTheDocument()
    })
    
    const logoutButton = screen.getByTestId('logout-button')
    await user.click(logoutButton)
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('calls checkAuth when component mounts', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: false,
    })

    render(<MockedAuthProvider />)
    
    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith('http://localhost:8080/api/admin', {
        credentials: 'include',
      })
    })
  })
})