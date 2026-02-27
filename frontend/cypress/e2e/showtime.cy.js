describe('Showtime Seat Selection', () => {
  beforeEach(() => {
    cy.visit('/')
    cy.get('a[href^="/showtime/"]').first().click()
  })

  it('should load showtime page', () => {
    cy.url().should('include', '/showtime/')
    cy.get('h1').should('contain', 'Select Your Seats')
  })

  it('should have back navigation link', () => {
    cy.get('a[href="/"]').should('contain', 'Back')
  })
})
