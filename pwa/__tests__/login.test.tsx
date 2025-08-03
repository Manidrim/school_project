import { render, screen, fireEvent, waitFor, act } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import Login from '../pages/login'
import { mockPush } from '../jest.setup'
import { MockAuthProvider } from './test-utils'

const MockedLogin = (authContextValue = {}) => (
  <MockAuthProvider value={authContextValue}>
    <Login />
  </MockAuthProvider>
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

  it('renders login page content', () => {
    render(<MockedLogin />)
    // Verify login page content is rendered
    expect(screen.getByText('Sign in to admin panel')).toBeInTheDocument()
  })

  it('redirects authenticated user to admin', async () => {
    const mockUser = { email: 'test@example.com', roles: ['ROLE_ADMIN'] }
    
    render(<MockedLogin user={mockUser} />)
    
    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith('/admin')
    })
  })

  it('handles input changes', async () => {
    const user = userEvent.setup()
    
    render(<MockedLogin />)
    
    const emailInput = screen.getByPlaceholderText('Email address')
    const passwordInput = screen.getByPlaceholderText('Password')
    
    await user.type(emailInput, 'test@example.com')
    await user.type(passwordInput, 'password123')
    
    expect(emailInput).toHaveValue('test@example.com')
    expect(passwordInput).toHaveValue('password123')
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
    const mockLogin = jest.fn().mockResolvedValue(true)
    
    await act(async () => {
      render(<MockedLogin login={mockLogin} user={null} />)
    })
    
    const emailInput = screen.getByPlaceholderText('Email address')
    const passwordInput = screen.getByPlaceholderText('Password')
    const submitButton = screen.getByRole('button', { name: /sign in/i })
    
    await act(async () => {
      await user.type(emailInput, 'admin@test.com')
      await user.type(passwordInput, 'password123')
      await user.click(submitButton)
    })
    
    await waitFor(() => {
      expect(mockLogin).toHaveBeenCalledWith('admin@test.com', 'password123')
      expect(mockPush).toHaveBeenCalledWith('/admin')
    })
  })

  it('shows error message on failed login', async () => {
    const user = userEvent.setup()
    const mockLogin = jest.fn().mockResolvedValue(false)
    
    await act(async () => {
      render(<MockedLogin login={mockLogin} user={null} />)
    })
    
    const emailInput = screen.getByPlaceholderText('Email address')
    const passwordInput = screen.getByPlaceholderText('Password')
    const submitButton = screen.getByRole('button', { name: /sign in/i })
    
    await act(async () => {
      await user.type(emailInput, 'admin@test.com')
      await user.type(passwordInput, 'wrongpassword')
      await user.click(submitButton)
    })
    
    await waitFor(() => {
      expect(screen.getByText('Invalid credentials')).toBeInTheDocument()
    })
  })

  it('shows loading state during login', async () => {
    const user = userEvent.setup()
    let resolveLogin: (value: boolean) => void
    const mockLogin = jest.fn().mockImplementation(() => new Promise(resolve => {
      resolveLogin = resolve
    }))
    
    await act(async () => {
      render(<MockedLogin login={mockLogin} user={null} />)
    })
    
    const emailInput = screen.getByPlaceholderText('Email address')
    const passwordInput = screen.getByPlaceholderText('Password')
    const submitButton = screen.getByRole('button', { name: /sign in/i })
    
    await act(async () => {
      await user.type(emailInput, 'admin@test.com')
      await user.type(passwordInput, 'password123')
    })
    
    await act(async () => {
      await user.click(submitButton)
    })
    
    expect(screen.getByText('Signing in...')).toBeInTheDocument()
    
    await act(async () => {
      resolveLogin!(true)
    })
  })
}) 