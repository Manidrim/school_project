import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AuthProvider, useAuth } from '../contexts/AuthContext'

const TestCoverageComponent = () => {
  const { user, loading, login, logout } = useAuth()
  
  const testFailedFetch = async () => {
    await login('test@test.com', 'wrong-password')
  }

  const testLogoutError = async () => {
    await logout()
  }
  
  return (
    <div>
      {loading && <div data-testid="loading">Loading...</div>}
      {user ? (
        <div>
          <div data-testid="user-email">{user.email}</div>
          <button onClick={testLogoutError} data-testid="logout-error">Logout Error</button>
        </div>
      ) : (
        <div>
          <div data-testid="not-authenticated">Not authenticated</div>
          <button onClick={testFailedFetch} data-testid="failed-fetch">Failed Fetch</button>
        </div>
      )}
    </div>
  )
}

describe('Auth Coverage Complete', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    global.fetch = jest.fn()
  })

  it('handles login with empty CSRF token response', async () => {
    const user = userEvent.setup()
    
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({ ok: false })
      .mockResolvedValueOnce({
        ok: true,
        text: () => Promise.resolve('<html>no token here</html>')
      })
      .mockResolvedValueOnce({ ok: false })

    render(
      <AuthProvider>
        <TestCoverageComponent />
      </AuthProvider>
    )
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
    
    const button = screen.getByTestId('failed-fetch')
    await user.click(button)
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('handles login with successful CSRF but failed login', async () => {
    const user = userEvent.setup()
    
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({ ok: false })
      .mockResolvedValueOnce({
        ok: true,
        text: () => Promise.resolve('<input name="_csrf_token" value="valid-token" />')
      })
      .mockResolvedValueOnce({ ok: false })

    render(
      <AuthProvider>
        <TestCoverageComponent />
      </AuthProvider>
    )
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
    
    const button = screen.getByTestId('failed-fetch')
    await user.click(button)
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })

  it('covers checkAuth setting loading false', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({ ok: false })

    render(
      <AuthProvider>
        <TestCoverageComponent />
      </AuthProvider>
    )
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })
})