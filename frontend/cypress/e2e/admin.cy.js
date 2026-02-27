describe('Admin Page', () => {
  beforeEach(() => {
    cy.visit('/admin')
  })

  it('should load the admin page', () => {
    cy.get('h1').should('contain', 'Admin Panel')
  })

  it('should display the add movie form', () => {
    cy.get('form').should('be.visible')
    cy.get('input[type="text"]').first().should('be.visible')
    cy.get('input[type="datetime-local"]').should('be.visible')
  })
})
