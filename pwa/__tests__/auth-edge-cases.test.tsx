import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AuthProvider, useAuth } from '../contexts/AuthContext'

const TestAuthEdgeCases = () => {
  const { user, loading, login, logout } = useAuth()
  
  const handleLoginFailure = async () => {
    try {
      await login('invalid', 'invalid')
    } catch {
      // Ignore error
    }
  }

  const handleLogoutError = async () => {
    try {
      await logout()
    } catch {
      // Ignore error  
    }
  }
  
  return (
    <div>
      {loading && <div data-testid="loading">Loading...</div>}
      {user ? (
        <div>
          <div data-testid="user-email">{user.email}</div>
          <button onClick={handleLogoutError} data-testid="logout-error-button">
            Logout with Error
          </button>
        </div>
      ) : (
        <div>
          <div data-testid="not-authenticated">Not authenticated</div>
          <button onClick={handleLoginFailure} data-testid="login-failure-button">
            Login Failure
          </button>
        </div>
      )}
    </div>
  )
}

describe('AuthContext Edge Cases for Coverage', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    global.fetch = jest.fn()
  })

  it('covers useAuth error when used outside provider', () => {
    const TestComponent = () => {
      try {
        useAuth()
        return <div>Should not reach here</div>
      } catch (error) {
        return <div data-testid="auth-error">Auth Error</div>
      }
    }
    
    render(<TestComponent />)
    expect(screen.getByTestId('auth-error')).toBeInTheDocument()
  })

  it('covers login failure path', async () => {
    const user = userEvent.setup()
    
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({ ok: false })
      .mockRejectedValue(new Error('Login failed'))

    render(
      <AuthProvider>
        <TestAuthEdgeCases />
      </AuthProvider>
    )
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
    
    const loginButton = screen.getByTestId('login-failure-button')
    await user.click(loginButton)
    
    await waitFor(() => {
      expect(screen.getByTestId('not-authenticated')).toBeInTheDocument()
    })
  })
})