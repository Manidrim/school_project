import { render, screen } from '@testing-library/react'
import Admin from '../../pages/admin/index'
import { MockAuthProvider, createMockUser } from '../test-utils'

describe('Admin Page Utility Functions', () => {
  it('renders loading screen correctly', () => {
    render(<MockAuthProvider value={{ loading: true, user: null }}>
      <Admin />
    </MockAuthProvider>)
    
    expect(screen.getByText('Loading...')).toBeInTheDocument()
  })

  it('returns null when user is null and not loading', () => {
    const { container } = render(<MockAuthProvider value={{ loading: false, user: null }}>
      <Admin />
    </MockAuthProvider>)
    
    expect(container.firstChild).toBeNull()
  })

  it('renders admin card with href', () => {
    const mockUser = createMockUser()
    
    render(<MockAuthProvider value={{ user: mockUser, loading: false }}>
      <Admin />
    </MockAuthProvider>)
    
    const apiDocsLink = screen.getByText('View API Docs')
    expect(apiDocsLink.closest('a')).toHaveAttribute('href', '/docs')
  })

  it('renders admin card with onClick button', () => {
    const mockUser = createMockUser()
    
    render(<MockAuthProvider value={{ user: mockUser, loading: false }}>
      <Admin />
    </MockAuthProvider>)
    
    const checkStatusButton = screen.getByText('Check Status')
    expect(checkStatusButton.tagName).toBe('BUTTON')
  })

  it('displays correct color schemes for cards', () => {
    const mockUser = createMockUser()
    
    render(<MockAuthProvider value={{ user: mockUser, loading: false }}>
      <Admin />
    </MockAuthProvider>)
    
    const apiCard = screen.getByText('API Documentation').closest('div')
    const dbCard = screen.getByText('Database Status').closest('div')
    const sysCard = screen.getAllByText('System Info')[0].closest('div')
    
    expect(apiCard).toHaveClass('bg-blue-50')
    expect(dbCard).toHaveClass('bg-green-50')
    expect(sysCard).toHaveClass('bg-purple-50')
  })
})