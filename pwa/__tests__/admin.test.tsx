import { render, screen, waitFor, act } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import Admin from '../pages/admin/index'
import { mockPush } from '../jest.setup'
import { MockAuthProvider, createMockUser } from './test-utils'

const MockedAdmin = ({ user = null, loading = false }: { user?: any, loading?: boolean }) => {
  return (
    <MockAuthProvider value={{ user, loading, login: jest.fn(), logout: jest.fn() }}>
      <Admin />
    </MockAuthProvider>
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

  it('renders admin dashboard when authenticated', async () => {
    const mockUser = createMockUser({ email: 'admin@test.com' })

    await act(async () => {
      render(<MockedAdmin user={mockUser} loading={false} />)
    })
    
    expect(screen.getByText('Admin Dashboard')).toBeInTheDocument()
    expect(screen.getByText('Welcome, admin@test.com')).toBeInTheDocument()
    expect(screen.getByText('Welcome to Admin Panel')).toBeInTheDocument()
  })

  it('renders module cards', async () => {
    const mockUser = createMockUser({ email: 'admin@test.com' })

    await act(async () => {
      render(<MockedAdmin user={mockUser} loading={false} />)
    })
    
    expect(screen.getByText('API Documentation')).toBeInTheDocument()
    expect(screen.getByText('Database Status')).toBeInTheDocument()
    expect(screen.getAllByText('System Info')[0]).toBeInTheDocument()
  })

  it('has logout functionality', async () => {
    const mockLogout = jest.fn()
    const mockUser = createMockUser({ email: 'admin@test.com' })

    const MockedAdminWithLogout = () => (
      <MockAuthProvider value={{ user: mockUser, loading: false, logout: mockLogout }}>
        <Admin />
      </MockAuthProvider>
    )

    const user = userEvent.setup()
    
    await act(async () => {
      render(<MockedAdminWithLogout />)
    })
    
    const logoutButton = screen.getByText('Logout')
    
    await act(async () => {
      await user.click(logoutButton)
    })
    
    expect(mockLogout).toHaveBeenCalled()
  })

  it('renders admin dashboard content', async () => {
    const mockUser = createMockUser({ email: 'admin@test.com' })

    await act(async () => {
      render(<MockedAdmin user={mockUser} loading={false} />)
    })
    
    // Verify admin dashboard content is rendered
    expect(screen.getByText('Admin Dashboard')).toBeInTheDocument()
    expect(screen.getByText('Welcome to Admin Panel')).toBeInTheDocument()
  })

  it('renders action buttons', async () => {
    const mockUser = createMockUser({ email: 'admin@test.com' })

    await act(async () => {
      render(<MockedAdmin user={mockUser} loading={false} />)
    })
    
    expect(screen.getByText('View API Docs')).toBeInTheDocument()
    expect(screen.getByText('Check Status')).toBeInTheDocument()
    expect(screen.getAllByText('System Info')[1]).toBeInTheDocument()
  })

  it('renders note about API Platform Admin', async () => {
    const mockUser = createMockUser({ email: 'admin@test.com' })

    await act(async () => {
      render(<MockedAdmin user={mockUser} loading={false} />)
    })
    
    expect(screen.getByText(/This is a simplified admin interface/)).toBeInTheDocument()
  })
})