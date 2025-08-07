import { render, screen, waitFor, act } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import Articles from '../../pages/admin/articles'
import { MockAuthProvider, createMockUser } from '../test-utils'

const mockArticles = {
  'hydra:member': [
    {
      id: 1,
      title: 'Test Article',
      content: 'Test content',
      isPublished: false,
      author: { email: 'admin@test.com' },
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z'
    }
  ]
}

const MockedArticles = ({ user = createMockUser(), loading = false }: any) => (
  <MockAuthProvider value={{ user, loading, logout: jest.fn() }}>
    <Articles />
  </MockAuthProvider>
)

describe('Admin Articles Page', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    global.fetch = jest.fn()
    global.confirm = jest.fn()
  })

  it('shows loading state', () => {
    render(<MockedArticles loading={true} />)
    expect(screen.getByText('Loading...')).toBeInTheDocument()
  })

  it('returns null when user is not authenticated', () => {
    const { container } = render(<MockedArticles user={null} loading={false} />)
    expect(container.firstChild).toBeNull()
  })

  it('renders articles page when authenticated', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      json: () => Promise.resolve(mockArticles)
    })

    await act(async () => {
      render(<MockedArticles />)
    })

    expect(screen.getByText('Articles Management')).toBeInTheDocument()
    expect(screen.getByText('Create New Article')).toBeInTheDocument()
    expect(screen.getByText('Articles List')).toBeInTheDocument()
  })

  it('fetches and displays articles on mount', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      json: () => Promise.resolve(mockArticles)
    })

    await act(async () => {
      render(<MockedArticles />)
    })

    await waitFor(() => {
      expect(screen.getByText('Test Article')).toBeInTheDocument()
    })

    expect(global.fetch).toHaveBeenCalledWith(
      'http://localhost:8080/api/articles',
      expect.objectContaining({
        credentials: 'include',
        headers: {
          'Accept': 'application/ld+json'
        }
      })
    )
  })

  it('creates new article successfully', async () => {
    const user = userEvent.setup()

    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockArticles)
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          id: 2,
          title: 'New Article',
          content: 'New content',
          isPublished: true
        })
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          'hydra:member': [
            ...mockArticles['hydra:member'],
            {
              id: 2,
              title: 'New Article',
              content: 'New content',
              isPublished: true,
              author: { email: 'admin@test.com' },
              createdAt: '2024-01-02T00:00:00Z',
              updatedAt: '2024-01-02T00:00:00Z'
            }
          ]
        })
      })

    await act(async () => {
      render(<MockedArticles />)
    })

    const titleInput = screen.getByLabelText('Title')
    const contentTextarea = screen.getByLabelText('Content')
    const publishCheckbox = screen.getByLabelText('Publish immediately')
    const submitButton = screen.getByRole('button', { name: 'Create Article' })

    await user.type(titleInput, 'New Article')
    await user.type(contentTextarea, 'New content')
    await user.click(publishCheckbox)
    await user.click(submitButton)

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith(
        'http://localhost:8080/api/articles',
        expect.objectContaining({
          method: 'POST',
          headers: {
            'Content-Type': 'application/ld+json',
            'Accept': 'application/ld+json'
          },
          credentials: 'include',
          body: JSON.stringify({
            title: 'New Article',
            content: 'New content',
            isPublished: true
          })
        })
      )
    })
  })

  it('edits existing article successfully', async () => {
    const user = userEvent.setup()

    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockArticles)
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          ...mockArticles['hydra:member'][0],
          title: 'Updated Article',
          isPublished: true
        })
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockArticles)
      })

    await act(async () => {
      render(<MockedArticles />)
    })

    await waitFor(() => {
      expect(screen.getByText('Test Article')).toBeInTheDocument()
    })

    const editButton = screen.getByRole('button', { name: 'Edit' })
    await user.click(editButton)

    const titleInput = screen.getByDisplayValue('Test Article')
    const submitButton = screen.getByRole('button', { name: 'Update Article' })

    await user.clear(titleInput)
    await user.type(titleInput, 'Updated Article')
    await user.click(submitButton)

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith(
        'http://localhost:8080/api/articles/1',
        expect.objectContaining({
          method: 'PUT',
          headers: {
            'Content-Type': 'application/ld+json',
            'Accept': 'application/ld+json'
          },
          credentials: 'include',
          body: JSON.stringify({
            title: 'Updated Article',
            content: 'Test content',
            isPublished: false
          })
        })
      )
    })
  })

  it('toggles article publication status', async () => {
    const user = userEvent.setup()

    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockArticles)
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          ...mockArticles['hydra:member'][0],
          isPublished: true
        })
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          'hydra:member': [{
            ...mockArticles['hydra:member'][0],
            isPublished: true
          }]
        })
      })

    await act(async () => {
      render(<MockedArticles />)
    })

    await waitFor(() => {
      expect(screen.getByText('Test Article')).toBeInTheDocument()
    })

    const publishButton = screen.getByRole('button', { name: 'Publish' })
    await user.click(publishButton)

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith(
        'http://localhost:8080/api/articles/1',
        expect.objectContaining({
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/merge-patch+json',
            'Accept': 'application/ld+json'
          },
          credentials: 'include',
          body: JSON.stringify({ isPublished: true })
        })
      )
    })
  })

  it('deletes article with confirmation', async () => {
    const user = userEvent.setup()

    ;(global.confirm as jest.Mock).mockReturnValue(true)
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockArticles)
      })
      .mockResolvedValueOnce({
        ok: true
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ 'hydra:member': [] })
      })

    await act(async () => {
      render(<MockedArticles />)
    })

    await waitFor(() => {
      expect(screen.getByText('Test Article')).toBeInTheDocument()
    })

    const deleteButton = screen.getByRole('button', { name: 'Delete' })
    await user.click(deleteButton)

    expect(global.confirm).toHaveBeenCalledWith('Are you sure you want to delete this article?')

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith(
        'http://localhost:8080/api/articles/1',
        expect.objectContaining({
          method: 'DELETE',
          credentials: 'include',
          headers: {
            'Accept': 'application/ld+json'
          }
        })
      )
    })
  })

  it('does not delete article when user cancels', async () => {
    const user = userEvent.setup()

    ;(global.confirm as jest.Mock).mockReturnValue(false)
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      json: () => Promise.resolve(mockArticles)
    })

    await act(async () => {
      render(<MockedArticles />)
    })

    await waitFor(() => {
      expect(screen.getByText('Test Article')).toBeInTheDocument()
    })

    const deleteButton = screen.getByRole('button', { name: 'Delete' })
    await user.click(deleteButton)

    expect(global.confirm).toHaveBeenCalled()
    expect(global.fetch).toHaveBeenCalledTimes(1) // Only initial fetch
  })

  it('clears form when clear button is clicked', async () => {
    const user = userEvent.setup()

    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      json: () => Promise.resolve(mockArticles)
    })

    await act(async () => {
      render(<MockedArticles />)
    })

    const titleInput = screen.getByLabelText('Title')
    const contentTextarea = screen.getByLabelText('Content')
    const clearButton = screen.getByRole('button', { name: 'Clear' })

    await user.type(titleInput, 'Some title')
    await user.type(contentTextarea, 'Some content')
    await user.click(clearButton)

    expect(titleInput).toHaveValue('')
    expect(contentTextarea).toHaveValue('')
  })

  it('shows empty state when no articles exist', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ 'hydra:member': [] })
    })

    await act(async () => {
      render(<MockedArticles />)
    })

    await waitFor(() => {
      expect(screen.getByText('No articles found.')).toBeInTheDocument()
    })
  })

  it('handles API errors gracefully', async () => {
    ;(global.fetch as jest.Mock).mockRejectedValue(new Error('Network error'))

    await act(async () => {
      render(<MockedArticles />)
    })

    // Should render the page without crashing
    expect(screen.getByText('Articles Management')).toBeInTheDocument()
    expect(screen.getByText('No articles found.')).toBeInTheDocument()
  })
})