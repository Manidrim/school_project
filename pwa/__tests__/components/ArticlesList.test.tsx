import { render, screen, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import ArticlesList from '../../components/admin/ArticlesList'

const mockArticles = [
  {
    id: 1,
    title: 'Published Article',
    content: 'This is a published article with some content that will be truncated for display.',
    createdAt: '2024-01-01T00:00:00Z',
    updatedAt: '2024-01-01T00:00:00Z',
    isPublished: true,
    author: {
      email: 'admin@test.com'
    },
    lastModifiedBy: {
      email: 'editor@test.com'
    }
  },
  {
    id: 2,
    title: 'Draft Article',
    content: 'This is a draft article that has not been published yet.',
    createdAt: '2024-01-02T00:00:00Z',
    updatedAt: '2024-01-02T00:00:00Z',
    isPublished: false,
    author: {
      email: 'author@test.com'
    }
  }
]

const mockOnEdit = jest.fn()
const mockOnDelete = jest.fn()
const mockOnTogglePublish = jest.fn()

describe('ArticlesList Component', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('renders articles list correctly', () => {
    render(
      <ArticlesList
        articles={mockArticles}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    expect(screen.getByText('Articles List')).toBeInTheDocument()
    expect(screen.getByText('Published Article')).toBeInTheDocument()
    expect(screen.getByText('Draft Article')).toBeInTheDocument()
  })

  it('displays correct publication status badges', () => {
    render(
      <ArticlesList
        articles={mockArticles}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    expect(screen.getByText('Published')).toBeInTheDocument()
    expect(screen.getByText('Draft')).toBeInTheDocument()
  })

  it('shows correct publish/unpublish button text', () => {
    render(
      <ArticlesList
        articles={mockArticles}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    // For published article, should show "Unpublish"
    const unpublishButton = screen.getByRole('button', { name: 'Unpublish' })
    expect(unpublishButton).toBeInTheDocument()

    // For draft article, should show "Publish"
    const publishButton = screen.getByRole('button', { name: 'Publish' })
    expect(publishButton).toBeInTheDocument()
  })

  it('handles publish button click correctly', async () => {
    const user = userEvent.setup()

    render(
      <ArticlesList
        articles={mockArticles}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    const publishButton = screen.getByRole('button', { name: 'Publish' })
    await user.click(publishButton)

    expect(mockOnTogglePublish).toHaveBeenCalledWith(2, true) // Article 2 (draft) should be published
  })

  it('handles unpublish button click correctly', async () => {
    const user = userEvent.setup()

    render(
      <ArticlesList
        articles={mockArticles}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    const unpublishButton = screen.getByRole('button', { name: 'Unpublish' })
    await user.click(unpublishButton)

    expect(mockOnTogglePublish).toHaveBeenCalledWith(1, false) // Article 1 (published) should be unpublished
  })

  it('handles edit button click', async () => {
    const user = userEvent.setup()

    render(
      <ArticlesList
        articles={mockArticles}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    const editButtons = screen.getAllByRole('button', { name: 'Edit' })
    await user.click(editButtons[0])

    expect(mockOnEdit).toHaveBeenCalledWith(mockArticles[0])
  })

  it('handles delete button click', async () => {
    const user = userEvent.setup()

    render(
      <ArticlesList
        articles={mockArticles}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    const deleteButtons = screen.getAllByRole('button', { name: 'Delete' })
    await user.click(deleteButtons[0])

    expect(mockOnDelete).toHaveBeenCalledWith(1)
  })

  it('displays truncated content correctly', () => {
    render(
      <ArticlesList
        articles={mockArticles}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    // Content should be truncated to 100 characters
    const truncatedContent = screen.getByText(/This is a published article with some content that will be truncated for display\.\.\./)
    expect(truncatedContent).toBeInTheDocument()
  })

  it('displays author email correctly', () => {
    render(
      <ArticlesList
        articles={mockArticles}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    expect(screen.getByText('admin@test.com')).toBeInTheDocument()
    expect(screen.getByText('author@test.com')).toBeInTheDocument()
  })

  it('formats dates correctly', () => {
    render(
      <ArticlesList
        articles={mockArticles}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    // Dates should be formatted as locale date strings
    const expectedDate1 = new Date('2024-01-01T00:00:00Z').toLocaleDateString()
    const expectedDate2 = new Date('2024-01-02T00:00:00Z').toLocaleDateString()

    expect(screen.getByText(expectedDate1)).toBeInTheDocument()
    expect(screen.getByText(expectedDate2)).toBeInTheDocument()
  })

  it('shows empty state when no articles', () => {
    render(
      <ArticlesList
        articles={[]}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    expect(screen.getByText('No articles found.')).toBeInTheDocument()
    expect(screen.queryByRole('table')).not.toBeInTheDocument()
  })

  it('renders table structure correctly', () => {
    render(
      <ArticlesList
        articles={mockArticles}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    expect(screen.getByRole('table')).toBeInTheDocument()
    expect(screen.getByText('Title')).toBeInTheDocument()
    expect(screen.getByText('Author')).toBeInTheDocument()
    expect(screen.getByText('Status')).toBeInTheDocument()
    expect(screen.getByText('Created')).toBeInTheDocument()
    expect(screen.getByText('Actions')).toBeInTheDocument()
  })

  it('has correct CSS classes for published status', () => {
    render(
      <ArticlesList
        articles={mockArticles}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    const publishedBadge = screen.getByText('Published')
    expect(publishedBadge).toHaveClass('bg-green-100', 'text-green-800')

    const draftBadge = screen.getByText('Draft')
    expect(draftBadge).toHaveClass('bg-gray-100', 'text-gray-800')
  })

  it('handles articles without lastModifiedBy gracefully', () => {
    const articlesWithoutModifier = [
      {
        ...mockArticles[1],
        lastModifiedBy: undefined
      }
    ]

    render(
      <ArticlesList
        articles={articlesWithoutModifier}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    expect(screen.getByText('Draft Article')).toBeInTheDocument()
  })

  it('handles very long content correctly', () => {
    const longContentArticle = [{
      id: 3,
      title: 'Long Content Article',
      content: 'A'.repeat(200), // 200 characters, should be truncated to 100
      createdAt: '2024-01-03T00:00:00Z',
      updatedAt: '2024-01-03T00:00:00Z',
      isPublished: false,
      author: {
        email: 'test@test.com'
      }
    }]

    render(
      <ArticlesList
        articles={longContentArticle}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onTogglePublish={mockOnTogglePublish}
      />
    )

    const truncatedText = 'A'.repeat(100) + '...'
    expect(screen.getByText(truncatedText)).toBeInTheDocument()
  })
})