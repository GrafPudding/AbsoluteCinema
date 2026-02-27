describe('Home Page', () => {
  beforeEach(() => {
    cy.visit('/')
  })

  it('should load the home page successfully', () => {
    cy.get('h1').should('contain', 'Now Playing')
  })

  it('should display movies from the API', () => {
    cy.get('[class*="rounded-xl"]')
      .first()
      .should('be.visible')
  })

  it('should show movie title', () => {
    cy.get('[class*="font-bold"]')
      .first()
      .should('not.be.empty')
  })

  it('should display showtime links', () => {
    cy.get('a[href^="/showtime/"]')
      .should('exist')
  })

  it('should navigate to showtime page when clicking a showtime', () => {
    cy.get('a[href^="/showtime/"]')
      .first()
      .click()
    
    cy.url()
      .should('include', '/showtime/')
  })

  it('should navigate to admin page', () => {
    cy.visit('/admin')
    cy.get('h1').should('contain', 'Admin Panel')
  })
})
