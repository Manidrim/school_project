import { renderHook, act, waitFor } from '@testing-library/react'
import useArticles from '../../hooks/useArticles'

const mockUser = {
  email: 'admin@test.com'
}

describe('useArticles Hook', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    global.fetch = jest.fn()
  })

  it('initializes with empty articles and correct form data', () => {
    const { result } = renderHook(() => useArticles(mockUser))

    expect(result.current.articles).toEqual([])
    expect(result.current.formData).toEqual({
      title: '',
      content: '',
      isPublished: false
    })
    expect(result.current.editingArticle).toBeNull()
    expect(result.current.loading).toBe(false)
  })

  it('fetches articles on mount when user is provided', async () => {
    const mockArticles = {
      'hydra:member': [
        {
          id: 1,
          title: 'Test Article',
          content: 'Test content',
          isPublished: true,
          author: { email: 'admin@test.com' },
          createdAt: '2024-01-01T00:00:00Z',
          updatedAt: '2024-01-01T00:00:00Z'
        }
      ]
    }

    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      json: () => Promise.resolve(mockArticles)
    })

    const { result } = renderHook(() => useArticles(mockUser))

    await waitFor(() => {
      expect(result.current.articles).toEqual(mockArticles['hydra:member'])
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

  it('does not fetch articles when user is null', () => {
    renderHook(() => useArticles(null))

    expect(global.fetch).not.toHaveBeenCalled()
  })

  it('handles submit for creating new article', async () => {
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ 'hydra:member': [] })
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          id: 1,
          title: 'New Article',
          content: 'New content',
          isPublished: true
        })
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ 'hydra:member': [] })
      })

    const { result } = renderHook(() => useArticles(mockUser))

    act(() => {
      result.current.setFormData({
        title: 'New Article',
        content: 'New content',
        isPublished: true
      })
    })

    const mockEvent = {
      preventDefault: jest.fn()
    } as any

    await act(async () => {
      await result.current.handleSubmit(mockEvent)
    })

    expect(mockEvent.preventDefault).toHaveBeenCalled()
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

  it('handles submit for updating existing article', async () => {
    const existingArticle = {
      id: 1,
      title: 'Original Title',
      content: 'Original content',
      isPublished: false,
      author: { email: 'admin@test.com' },
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z'
    }

    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ 'hydra:member': [] })
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ ...existingArticle, title: 'Updated Title' })
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ 'hydra:member': [] })
      })

    const { result } = renderHook(() => useArticles(mockUser))

    act(() => {
      result.current.handleEdit(existingArticle)
    })

    act(() => {
      result.current.setFormData({
        title: 'Updated Title',
        content: 'Original content',
        isPublished: false
      })
    })

    const mockEvent = {
      preventDefault: jest.fn()
    } as any

    await act(async () => {
      await result.current.handleSubmit(mockEvent)
    })

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
          title: 'Updated Title',
          content: 'Original content',
          isPublished: false
        })
      })
    )
  })

  it('handles toggle publish correctly', async () => {
    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ 'hydra:member': [] })
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          id: 1,
          isPublished: true
        })
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ 'hydra:member': [] })
      })

    const { result } = renderHook(() => useArticles(mockUser))

    await act(async () => {
      await result.current.handleTogglePublish(1, true)
    })

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

  it('handles delete article correctly', async () => {
    const mockConfirm = jest.spyOn(window, 'confirm').mockReturnValue(true)

    ;(global.fetch as jest.Mock)
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ 'hydra:member': [] })
      })
      .mockResolvedValueOnce({
        ok: true
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ 'hydra:member': [] })
      })

    const { result } = renderHook(() => useArticles(mockUser))

    await act(async () => {
      await result.current.handleDelete(1)
    })

    expect(mockConfirm).toHaveBeenCalledWith('Are you sure you want to delete this article?')
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

    mockConfirm.mockRestore()
  })

  it('does not delete article when user cancels confirmation', async () => {
    const mockConfirm = jest.spyOn(window, 'confirm').mockReturnValue(false)

    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ 'hydra:member': [] })
    })

    const { result } = renderHook(() => useArticles(mockUser))

    await act(async () => {
      await result.current.handleDelete(1)
    })

    expect(mockConfirm).toHaveBeenCalled()
    expect(global.fetch).toHaveBeenCalledTimes(1) // Only the initial fetch

    mockConfirm.mockRestore()
  })

  it('handles edit article correctly', () => {
    const article = {
      id: 1,
      title: 'Test Article',
      content: 'Test content',
      isPublished: true,
      author: { email: 'admin@test.com' },
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z'
    }

    const { result } = renderHook(() => useArticles(mockUser))

    act(() => {
      result.current.handleEdit(article)
    })

    expect(result.current.formData).toEqual({
      title: 'Test Article',
      content: 'Test content',
      isPublished: true
    })
    expect(result.current.editingArticle).toEqual(article)
  })

  it('handles API errors gracefully', async () => {
    ;(global.fetch as jest.Mock).mockRejectedValue(new Error('API Error'))

    const { result } = renderHook(() => useArticles(mockUser))

    // Should not throw error, just handle gracefully
    await waitFor(() => {
      expect(result.current.articles).toEqual([])
    })
  })
})