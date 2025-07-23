import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AuthProvider, useAuth } from '../contexts/AuthContext'
import { mockPush } from '../jest.setup'

const TestComponent = () => {
  const { user, loading, login, logout } = useAuth()
  
  return (
    <div>
      {loading && <div data-testid="loading">Loading...</div>}
      {user ? (
        <div>
          <div data-testid="user-email">{user.email}</div>
          <button onClick={logout} data-testid="logout-button">
            Logout
          </button>
        </div>
      ) : (
        <div>
          <div data-testid="not-authenticated">Not authenticated</div>
          <button
            onClick={() => login('test@example.com', 'password')}
            data-testid="login-button"
          >
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

describe('AuthContext', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    global.fetch = jest.fn()
  })

  it('shows loading state initially', () => {
    ;(global.fetch as jest.Mock).mockImplementation(
      () => new Promise(resolve => setTimeout(resolve, 100))
    )

    render(<MockedAuthProvider />)
    
    expect(screen.getByTestId('loading')).toBeInTheDocument()
  })

  it('shows not authenticated when no user', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: false,
    })

    render(<MockedAuthProvider />)
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('shows user info when authenticated', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      text: () => Promise.resolve('Welcome, test@example.com!'),
    })

    render(<MockedAuthProvider />)
    
    await waitFor(() => {
      expect(screen.getByTestId('user-email')).toHaveTextContent('test@example.com')
    })
  })

  it('handles login successfully', async () => {
    const user = userEvent.setup()
    
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: false,
      })
      .mockResolvedValueOnce({
        ok: true,
        text: () => Promise.resolve('<input name="_csrf_token" value="test-token" />'),
      })
      .mockResolvedValueOnce({
        ok: true,
      })

    render(<MockedAuthProvider />)
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
    
    const loginButton = screen.getByTestId('login-button')
    await user.click(loginButton)
    
    await waitFor(() => {
      expect(screen.getByTestId('user-email')).toHaveTextContent('test@example.com')
    })
  })

  it('handles logout', async () => {
    const user = userEvent.setup()
    
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        text: () => Promise.resolve('Welcome, test@example.com!'),
      })
      .mockResolvedValueOnce({
        ok: true,
      })

    render(<MockedAuthProvider />)
    
    await waitFor(() => {
      expect(screen.getByTestId('user-email')).toBeInTheDocument()
    })
    
    const logoutButton = screen.getByTestId('logout-button')
    await user.click(logoutButton)
    
    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith('/login')
    })
  })
}) 