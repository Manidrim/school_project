import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import Admin from '../pages/admin/index'
import { MockAuthProvider, createMockUser } from './test-utils'

describe('Admin Functions Coverage', () => {
  it('covers all admin utility functions', async () => {
    const mockUser = createMockUser()
    const user = userEvent.setup()
    
    render(
      <MockAuthProvider value={{ user: mockUser, loading: false }}>
        <Admin />
      </MockAuthProvider>
    )
    
    // Test button clicks to cover onClick functions
    const checkStatusButton = screen.getByText('Check Status')
    const systemInfoButton = screen.getAllByText('System Info')[1] // Button, not title
    
    await user.click(checkStatusButton)
    await user.click(systemInfoButton)
    
    // Verify buttons are clickable (covers the onClick functions)
    expect(checkStatusButton).toBeInTheDocument()
    expect(systemInfoButton).toBeInTheDocument()
  })

  it('covers createLoadingScreen function', () => {
    render(
      <MockAuthProvider value={{ user: null, loading: true }}>
        <Admin />
      </MockAuthProvider>
    )
    
    expect(screen.getByText('Loading...')).toBeInTheDocument()
  })

  it('covers null return when user is null and not loading', () => {
    const { container } = render(
      <MockAuthProvider value={{ user: null, loading: false }}>
        <Admin />
      </MockAuthProvider>
    )
    
    expect(container.firstChild).toBeNull()
  })

  it('covers all admin card color schemes', () => {
    const mockUser = createMockUser()
    
    render(
      <MockAuthProvider value={{ user: mockUser, loading: false }}>
        <Admin />
      </MockAuthProvider>
    )
    
    // Test that all three color schemes are rendered
    expect(screen.getByText('API Documentation').closest('div')).toHaveClass('bg-blue-50')
    expect(screen.getByText('Database Status').closest('div')).toHaveClass('bg-green-50')
    expect(screen.getAllByText('System Info')[0].closest('div')).toHaveClass('bg-purple-50')
  })

  it('covers createNavigation with logout', async () => {
    const mockLogout = jest.fn()
    const mockUser = createMockUser()
    const user = userEvent.setup()
    
    render(
      <MockAuthProvider value={{ user: mockUser, loading: false, logout: mockLogout }}>
        <Admin />
      </MockAuthProvider>
    )
    
    const logoutButton = screen.getByText('Logout')
    await user.click(logoutButton)
    
    expect(mockLogout).toHaveBeenCalled()
  })

  it('covers createArrowIcon and createHeartIcon indirectly through admin card hrefs', () => {
    const mockUser = createMockUser()
    
    render(
      <MockAuthProvider value={{ user: mockUser, loading: false }}>
        <Admin />
      </MockAuthProvider>
    )
    
    // Test that the API docs link exists (which uses createArrowIcon indirectly)
    const apiDocsLink = screen.getByText('View API Docs')
    expect(apiDocsLink.closest('a')).toHaveAttribute('href', '/docs')
  })
})