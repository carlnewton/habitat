describe('admin', function() {

  before(function() {
    cy.loadFixtureGroups(['setup']);
  })

  describe('setup dashboard', function() {

    beforeEach(function() {
      cy.then(Cypress.session.clearAllSavedSessions)
    })

    it('links to the admin dashboard', function() {
      cy.loginUser('admin');
      cy.visit('/');
      cy.getElement('admin-dashboard-link').should('be.visible');
      cy.getElement('admin-dashboard-link').click();
      cy.url().should('include', '/admin');
    })

  })

})
