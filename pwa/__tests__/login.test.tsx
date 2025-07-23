import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AuthProvider } from '../contexts/AuthContext'
import Login from '../pages/login'
import { mockPush } from '../jest.setup'

const MockedLogin = () => (
  <AuthProvider>
    <Login />
  </AuthProvider>
)

describe('Login Page', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    global.fetch = jest.fn()
  })

  it('renders login form', () => {
    render(<MockedLogin />)
    
    expect(screen.getByText('Sign in to admin panel')).toBeInTheDocument()
    expect(screen.getByPlaceholderText('Email address')).toBeInTheDocument()
    expect(screen.getByPlaceholderText('Password')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /sign in/i })).toBeInTheDocument()
  })

  it('shows validation errors for empty fields', async () => {
    const user = userEvent.setup()
    render(<MockedLogin />)
    
    const submitButton = screen.getByRole('button', { name: /sign in/i })
    await user.click(submitButton)
    
    expect(screen.getByPlaceholderText('Email address')).toBeInvalid()
  })

  it('handles successful login', async () => {
    const user = userEvent.setup()
    
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        text: () => Promise.resolve('<input name="_csrf_token" value="test-token" />'),
      })
      .mockResolvedValueOnce({
        ok: true,
      })

    render(<MockedLogin />)
    
    const emailInput = screen.getByPlaceholderText('Email address')
    const passwordInput = screen.getByPlaceholderText('Password')
    const submitButton = screen.getByRole('button', { name: /sign in/i })
    
    await user.type(emailInput, 'admin@test.com')
    await user.type(passwordInput, 'password123')
    await user.click(submitButton)
    
    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith('/admin')
    })
  })

  it('shows error message on failed login', async () => {
    const user = userEvent.setup()
    
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        text: () => Promise.resolve('<input name="_csrf_token" value="test-token" />'),
      })
      .mockResolvedValueOnce({
        ok: false,
      })

    render(<MockedLogin />)
    
    const emailInput = screen.getByPlaceholderText('Email address')
    const passwordInput = screen.getByPlaceholderText('Password')
    const submitButton = screen.getByRole('button', { name: /sign in/i })
    
    await user.type(emailInput, 'admin@test.com')
    await user.type(passwordInput, 'wrongpassword')
    await user.click(submitButton)
    
    await waitFor(() => {
      expect(screen.getByText('Invalid credentials')).toBeInTheDocument()
    })
  })

  it('shows loading state during login', async () => {
    const user = userEvent.setup()
    
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        text: () => Promise.resolve('<input name="_csrf_token" value="test-token" />'),
      })
      .mockImplementationOnce(() => new Promise(resolve => setTimeout(resolve, 100)))

    render(<MockedLogin />)
    
    const emailInput = screen.getByPlaceholderText('Email address')
    const passwordInput = screen.getByPlaceholderText('Password')
    const submitButton = screen.getByRole('button', { name: /sign in/i })
    
    await user.type(emailInput, 'admin@test.com')
    await user.type(passwordInput, 'password123')
    await user.click(submitButton)
    
    expect(screen.getByText('Signing in...')).toBeInTheDocument()
  })
}) 