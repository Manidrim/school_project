import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import ArticleForm from '../../components/admin/ArticleForm'

const mockFormData = {
  title: '',
  content: '',
  isPublished: false
}

const mockSetFormData = jest.fn()
const mockOnSubmit = jest.fn()

describe('ArticleForm Component', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('renders article form correctly for creating new article', () => {
    render(
      <ArticleForm
        formData={mockFormData}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={false}
        loading={false}
      />
    )

    expect(screen.getByText('Create New Article')).toBeInTheDocument()
    expect(screen.getByLabelText('Title')).toBeInTheDocument()
    expect(screen.getByLabelText('Content')).toBeInTheDocument()
    expect(screen.getByLabelText('Publish immediately')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Create Article' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Clear' })).toBeInTheDocument()
  })

  it('renders article form correctly for editing existing article', () => {
    render(
      <ArticleForm
        formData={mockFormData}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={true}
        loading={false}
      />
    )

    expect(screen.getByText('Edit Article')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Update Article' })).toBeInTheDocument()
  })

  it('displays form values correctly', () => {
    const formDataWithValues = {
      title: 'Test Article',
      content: 'Test content for the article',
      isPublished: true
    }

    render(
      <ArticleForm
        formData={formDataWithValues}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={false}
        loading={false}
      />
    )

    expect(screen.getByDisplayValue('Test Article')).toBeInTheDocument()
    expect(screen.getByDisplayValue('Test content for the article')).toBeInTheDocument()
    expect(screen.getByRole('checkbox', { name: 'Publish immediately' })).toBeChecked()
  })

  it('handles title input changes', async () => {
    const user = userEvent.setup()

    render(
      <ArticleForm
        formData={mockFormData}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={false}
        loading={false}
      />
    )

    const titleInput = screen.getByLabelText('Title')
    await user.type(titleInput, 'New Article Title')

    expect(mockSetFormData).toHaveBeenCalledWith({
      ...mockFormData,
      title: 'New Article Title'
    })
  })

  it('handles content input changes', async () => {
    const user = userEvent.setup()

    render(
      <ArticleForm
        formData={mockFormData}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={false}
        loading={false}
      />
    )

    const contentTextarea = screen.getByLabelText('Content')
    await user.type(contentTextarea, 'Article content here')

    expect(mockSetFormData).toHaveBeenCalledWith({
      ...mockFormData,
      content: 'Article content here'
    })
  })

  it('handles publication checkbox changes', async () => {
    const user = userEvent.setup()

    render(
      <ArticleForm
        formData={mockFormData}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={false}
        loading={false}
      />
    )

    const publishCheckbox = screen.getByRole('checkbox', { name: 'Publish immediately' })
    await user.click(publishCheckbox)

    expect(mockSetFormData).toHaveBeenCalledWith({
      ...mockFormData,
      isPublished: true
    })
  })

  it('handles form submission', async () => {
    const user = userEvent.setup()

    render(
      <ArticleForm
        formData={mockFormData}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={false}
        loading={false}
      />
    )

    const form = screen.getByRole('form')
    fireEvent.submit(form)

    expect(mockOnSubmit).toHaveBeenCalled()
  })

  it('handles clear button click', async () => {
    const user = userEvent.setup()

    render(
      <ArticleForm
        formData={mockFormData}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={false}
        loading={false}
      />
    )

    const clearButton = screen.getByRole('button', { name: 'Clear' })
    await user.click(clearButton)

    expect(mockSetFormData).toHaveBeenCalledWith({
      title: '',
      content: '',
      isPublished: false
    })
  })

  it('shows loading state correctly', () => {
    render(
      <ArticleForm
        formData={mockFormData}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={false}
        loading={true}
      />
    )

    const submitButton = screen.getByRole('button', { name: 'Saving...' })
    expect(submitButton).toBeDisabled()
  })

  it('shows loading state for editing', () => {
    render(
      <ArticleForm
        formData={mockFormData}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={true}
        loading={true}
      />
    )

    const submitButton = screen.getByRole('button', { name: 'Saving...' })
    expect(submitButton).toBeDisabled()
  })

  it('handles null/undefined form data gracefully', () => {
    const incompleteFormData = {
      title: undefined,
      content: null,
      isPublished: undefined
    } as any

    render(
      <ArticleForm
        formData={incompleteFormData}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={false}
        loading={false}
      />
    )

    const titleInput = screen.getByLabelText('Title')
    const contentTextarea = screen.getByLabelText('Content')
    expect(titleInput).toHaveValue('')
    expect(contentTextarea).toHaveValue('')
    expect(screen.getByRole('checkbox', { name: 'Publish immediately' })).not.toBeChecked()
  })

  it('prevents form submission when loading', async () => {
    const user = userEvent.setup()

    render(
      <ArticleForm
        formData={mockFormData}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={false}
        loading={true}
      />
    )

    const submitButton = screen.getByRole('button', { name: 'Saving...' })
    await user.click(submitButton)

    // Form should not submit when loading
    expect(mockOnSubmit).not.toHaveBeenCalled()
  })

  it('has correct accessibility attributes', () => {
    render(
      <ArticleForm
        formData={mockFormData}
        setFormData={mockSetFormData}
        onSubmit={mockOnSubmit}
        isEditing={false}
        loading={false}
      />
    )

    const titleInput = screen.getByLabelText('Title')
    const contentTextarea = screen.getByLabelText('Content')
    const publishCheckbox = screen.getByLabelText('Publish immediately')

    expect(titleInput).toHaveAttribute('required')
    expect(contentTextarea).toHaveAttribute('required')
    expect(publishCheckbox).toHaveAttribute('type', 'checkbox')
  })
})