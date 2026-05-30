describe.only('heart', function() {

  beforeEach(function() {
    cy.loadFixtureGroups([
      'posts',
    ]);
    cy.then(Cypress.session.clearAllSavedSessions)
  })

  it('offers anonymous users to sign in when attempting to heart a post', function() {
    cy.getElement('heart').click();
  })

})
