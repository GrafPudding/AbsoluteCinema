// eslint-disable-next-line no-undef
Cypress.Commands.add('seedDatabase', () => {
  cy.request('POST', 'http://127.0.0.1:8000/api/testing/seed')
})
