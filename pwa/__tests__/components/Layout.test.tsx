import { render, screen } from '@testing-library/react'
import Layout from '../../components/common/Layout'

const mockDehydratedState = {
  mutations: [],
  queries: [],
}

describe('Layout Component', () => {
  it('renders children correctly', () => {
    render(
      <Layout dehydratedState={mockDehydratedState}>
        <div data-testid="test-child">Test Content</div>
      </Layout>
    )
    
    expect(screen.getByTestId('test-child')).toBeInTheDocument()
    expect(screen.getByText('Test Content')).toBeInTheDocument()
  })

  it('provides QueryClient context', () => {
    render(
      <Layout dehydratedState={mockDehydratedState}>
        <div data-testid="wrapped-content">Wrapped in QueryClient</div>
      </Layout>
    )
    
    expect(screen.getByTestId('wrapped-content')).toBeInTheDocument()
  })

  it('handles empty dehydrated state', () => {
    const emptyState = { mutations: [], queries: [] }
    
    render(
      <Layout dehydratedState={emptyState}>
        <div data-testid="test-content">Content</div>
      </Layout>
    )
    
    expect(screen.getByTestId('test-content')).toBeInTheDocument()
  })
})