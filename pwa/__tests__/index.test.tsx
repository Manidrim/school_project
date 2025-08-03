import { render, screen } from '@testing-library/react'
import Welcome from '../pages/index'

jest.mock('next/image', () => ({
  __esModule: true,
  default: (props: any) => {
    // eslint-disable-next-line @next/next/no-img-element
    return <img {...props} />
  },
}))

jest.mock('@fontsource/poppins', () => ({}))
jest.mock('@fontsource/poppins/600.css', () => ({}))
jest.mock('@fontsource/poppins/700.css', () => ({}))

describe('Welcome Page', () => {
  beforeEach(() => {
    render(<Welcome />)
  })

  it('renders welcome message', () => {
    expect(screen.getByText('Welcome to')).toBeInTheDocument()
  })

  it('renders API Platform logo', () => {
    expect(screen.getByAltText('API Platform')).toBeInTheDocument()
  })

  it('renders service cards', () => {
    expect(screen.getByText('API')).toBeInTheDocument()
    expect(screen.getByText('Admin')).toBeInTheDocument()
    expect(screen.getByText('Mercure debugger')).toBeInTheDocument()
  })

  it('renders social media links', () => {
    expect(screen.getByTitle('API Platform on Twitter')).toBeInTheDocument()
    expect(screen.getByTitle('API Platform on Mastodon')).toBeInTheDocument()
    expect(screen.getByTitle('API Platform on Github')).toBeInTheDocument()
    expect(screen.getByTitle('Need help?')).toBeInTheDocument()
  })

  it('renders get started button', () => {
    expect(screen.getByText('Get started')).toBeInTheDocument()
    const getStartedLink = screen.getByText('Get started').closest('a')
    expect(getStartedLink).toHaveAttribute('href', 'https://api-platform.com/docs/')
  })

  it('renders made with love section', () => {
    expect(screen.getByText('Made with')).toBeInTheDocument()
    expect(screen.getByAltText('Les-Tilleuls.coop')).toBeInTheDocument()
  })

  it('renders available services section', () => {
    expect(screen.getByText('Available services:')).toBeInTheDocument()
  })

  it('renders follow us section', () => {
    expect(screen.getByText('Follow us')).toBeInTheDocument()
  })

  it('has correct page title', () => {
    expect(document.title).toBe('Welcome to API Platform!')
  })
})