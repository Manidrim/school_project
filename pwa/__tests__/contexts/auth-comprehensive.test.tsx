import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AuthProvider, useAuth } from '../../contexts/AuthContext'
import { ldJsonHeaders, mockAdminDashboardFetchPayload } from '../mockApiResponses'

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
      headers: ldJsonHeaders,
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
      .mockResolvedValueOnce(mockAdminDashboardFetchPayload('test@example.com'))
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

  it('treats checkAuth HTML content-type as unauthenticated', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      headers: {
        get: (name: string): string | null =>
          name.toLowerCase() === 'content-type' ? 'text/html; charset=utf-8' : null,
      },
    })

    render(<MockedAuthProvider />)

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('returns empty csrf when csrf endpoint responds not ok', async () => {
    const user = userEvent.setup()

    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({ ok: false })
      .mockResolvedValueOnce({ ok: false, json: () => Promise.resolve({}) })
      .mockResolvedValueOnce({ ok: false })

    render(<MockedAuthProvider />)

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })

    await user.click(screen.getByTestId('login-button'))

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('returns empty csrf when csrf json omits token', async () => {
    const user = userEvent.setup()

    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({ ok: false })
      .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({}) })
      .mockResolvedValueOnce({ ok: false })

    render(<MockedAuthProvider />)

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })

    await user.click(screen.getByTestId('login-button'))

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('returns empty csrf when csrf-token fetch throws', async () => {
    const user = userEvent.setup()

    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({ ok: false })
      .mockRejectedValueOnce(new Error('csrf unavailable'))
      .mockResolvedValueOnce({ ok: false })

    render(<MockedAuthProvider />)

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })

    await user.click(screen.getByTestId('login-button'))

    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('uses relative API URLs when NEXT_PUBLIC_API_URL is empty', async () => {
    const prev = process.env.NEXT_PUBLIC_API_URL
    process.env.NEXT_PUBLIC_API_URL = ''
    ;(global.fetch as jest.Mock).mockResolvedValue({ ok: false })

    render(<MockedAuthProvider />)

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith('/api/admin', {
        credentials: 'include',
        headers: { Accept: 'application/ld+json' },
      })
    })

    process.env.NEXT_PUBLIC_API_URL = prev
  })

  it('authenticates when checkAuth content-type is application/json', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      headers: {
        get: (name: string): string | null =>
          name.toLowerCase() === 'content-type' ? 'application/json' : null,
      },
      json: () =>
        Promise.resolve({
          title: 'Admin Dashboard',
          user: { email: 'json@test.com', roles: ['ROLE_ADMIN'] },
        }),
    })

    render(<MockedAuthProvider />)

    await waitFor(() => {
      expect(screen.getByTestId('user-email')).toHaveTextContent('json@test.com')
    })
  })

  it('ignores checkAuth payload when user field is missing', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      headers: ldJsonHeaders,
      json: () => Promise.resolve({ title: 'Admin Dashboard' }),
    })

    render(<MockedAuthProvider />)

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
        headers: { Accept: 'application/ld+json' },
      })
    })
  })
})