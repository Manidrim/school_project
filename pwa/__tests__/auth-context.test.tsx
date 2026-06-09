import { render, renderHook, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AuthProvider, useAuth } from '../contexts/AuthContext'
import { mockPush } from '../jest.setup'
import { mockAdminDashboardFetchPayload } from './mockApiResponses'

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

  it('useAuth throws when used outside AuthProvider', () => {
    expect(() => {
      renderHook(() => useAuth())
    }).toThrow('useAuth must be used within an AuthProvider')
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
    ;(global.fetch as jest.Mock).mockResolvedValue(
      mockAdminDashboardFetchPayload('test@example.com'),
    )

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
        json: () => Promise.resolve({ csrf_token: 'test-token' }),
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            user: {
              email: 'test@example.com',
              roles: ['ROLE_ADMIN'],
            },
          }),
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
      .mockResolvedValueOnce(mockAdminDashboardFetchPayload('test@example.com'))
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

  it('login keeps user unauthenticated when POST responds not ok', async () => {
    const u = userEvent.setup()
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({ ok: false })
      .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ csrf_token: 't' }) })
      .mockResolvedValueOnce({ ok: false })

    render(<MockedAuthProvider />)

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })

    await u.click(screen.getByTestId('login-button'))

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('login keeps user unauthenticated when success payload omits user', async () => {
    const u = userEvent.setup()
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({ ok: false })
      .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ csrf_token: 't' }) })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true }),
      })

    render(<MockedAuthProvider />)

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })

    await u.click(screen.getByTestId('login-button'))

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('uses relative auth URLs when env base is empty', async () => {
    const prev = process.env.NEXT_PUBLIC_API_URL
    process.env.NEXT_PUBLIC_API_URL = ''
    const u = userEvent.setup()
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({ ok: false })
      .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ csrf_token: 'c' }) })
      .mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            success: true,
            user: { email: 'rel@test.com', roles: ['ROLE_ADMIN'] },
          }),
      })

    render(<MockedAuthProvider />)

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })

    await u.click(screen.getByTestId('login-button'))

    await waitFor(() => {
      expect(screen.getByTestId('user-email')).toHaveTextContent('rel@test.com')
    })

    expect(global.fetch).toHaveBeenCalledWith('/api/auth/csrf-token', { credentials: 'include' })
    expect(global.fetch).toHaveBeenCalledWith(
      '/api/auth/login',
      expect.objectContaining({ method: 'POST' }),
    )

    process.env.NEXT_PUBLIC_API_URL = prev
  })

  it('logout posts to relative path when env base is empty', async () => {
    const prev = process.env.NEXT_PUBLIC_API_URL
    process.env.NEXT_PUBLIC_API_URL = ''
    const u = userEvent.setup()
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce(mockAdminDashboardFetchPayload('out@test.com'))
      .mockResolvedValueOnce({ ok: true })

    render(<MockedAuthProvider />)

    await waitFor(() => {
      expect(screen.getByTestId('user-email')).toBeInTheDocument()
    })

    await u.click(screen.getByTestId('logout-button'))

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith('/api/auth/logout', {
        method: 'POST',
        credentials: 'include',
      })
    })

    process.env.NEXT_PUBLIC_API_URL = prev
  })

  it('login keeps user unauthenticated when login fetch throws', async () => {
    const u = userEvent.setup()
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({ ok: false })
      .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ csrf_token: 't' }) })
      .mockRejectedValueOnce(new Error('login failed'))

    render(<MockedAuthProvider />)

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })

    await u.click(screen.getByTestId('login-button'))

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })
})
