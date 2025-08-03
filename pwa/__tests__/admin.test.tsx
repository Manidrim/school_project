import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AuthProvider } from '../contexts/AuthContext'
import Admin from '../pages/admin/index'
import { mockPush } from '../jest.setup'

const MockedAdmin = ({ user = null, loading = false }: { user?: any, loading?: boolean }) => {
  const mockAuthContext = {
    user,
    loading,
    login: jest.fn(),
    logout: jest.fn(),
  }

  return (
    <AuthProvider value={mockAuthContext}>
      <Admin />
    </AuthProvider>
  )
}

describe('Admin Page', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('shows loading state', () => {
    render(<MockedAdmin loading={true} />)
    expect(screen.getByText('Loading...')).toBeInTheDocument()
  })

  it('redirects to login when not authenticated', async () => {
    render(<MockedAdmin user={null} loading={false} />)
    
    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith('/login')
    })
  })

  it('renders admin dashboard when authenticated', () => {
    const mockUser = {
      email: 'admin@test.com',
      roles: ['ROLE_ADMIN']
    }

    render(<MockedAdmin user={mockUser} loading={false} />)
    
    expect(screen.getByText('Admin Dashboard')).toBeInTheDocument()
    expect(screen.getByText('Welcome, admin@test.com')).toBeInTheDocument()
    expect(screen.getByText('Welcome to Admin Panel')).toBeInTheDocument()
  })

  it('renders module cards', () => {
    const mockUser = {
      email: 'admin@test.com',
      roles: ['ROLE_ADMIN']
    }

    render(<MockedAdmin user={mockUser} loading={false} />)
    
    expect(screen.getByText('API Documentation')).toBeInTheDocument()
    expect(screen.getByText('Database Status')).toBeInTheDocument()
    expect(screen.getByText('System Info')).toBeInTheDocument()
  })

  it('has logout functionality', async () => {
    const mockLogout = jest.fn()
    const mockUser = {
      email: 'admin@test.com',
      roles: ['ROLE_ADMIN']
    }

    const MockedAdminWithLogout = () => {
      const mockAuthContext = {
        user: mockUser,
        loading: false,
        login: jest.fn(),
        logout: mockLogout,
      }

      return (
        <AuthProvider value={mockAuthContext}>
          <Admin />
        </AuthProvider>
      )
    }

    const user = userEvent.setup()
    render(<MockedAdminWithLogout />)
    
    const logoutButton = screen.getByText('Logout')
    await user.click(logoutButton)
    
    expect(mockLogout).toHaveBeenCalled()
  })

  it('has correct page title', () => {
    const mockUser = {
      email: 'admin@test.com',
      roles: ['ROLE_ADMIN']
    }

    render(<MockedAdmin user={mockUser} loading={false} />)
    expect(document.title).toBe('Admin Dashboard')
  })

  it('renders action buttons', () => {
    const mockUser = {
      email: 'admin@test.com',
      roles: ['ROLE_ADMIN']
    }

    render(<MockedAdmin user={mockUser} loading={false} />)
    
    expect(screen.getByText('View API Docs')).toBeInTheDocument()
    expect(screen.getByText('Check Status')).toBeInTheDocument()
    expect(screen.getByText('System Info')).toBeInTheDocument()
  })

  it('renders note about API Platform Admin', () => {
    const mockUser = {
      email: 'admin@test.com',
      roles: ['ROLE_ADMIN']
    }

    render(<MockedAdmin user={mockUser} loading={false} />)
    
    expect(screen.getByText(/This is a simplified admin interface/)).toBeInTheDocument()
  })
})