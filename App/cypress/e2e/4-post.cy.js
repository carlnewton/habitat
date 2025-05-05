describe('post', function() {

  before(function() {
    cy.resetDatabase();
    cy.loadFixtureGroups(['users']);
    cy.then(Cypress.session.clearAllSavedSessions)
  })

  beforeEach(function() {
    cy.loginUser('neo');
  })

  it('prevents a post without a title', function() {
    cy.visit('/post');
    cy.getElement('body').type('This is the body of the basic post')
    cy.getElement('submit').click();
    cy.getElement('title-errors').should('be.visible');
  })

  it('allows the creation of a basic post', function() {
    cy.visit('/post');
    cy.getElement('title').type('This is the title of the basic post');
    cy.getElement('body').type('This is the body of the basic post');
    cy.getElement('submit').click();
    cy.url().should('match', /\/post\/\d+$/)
    cy.getElement('success-message').should('be.visible');
    cy.getElement('title').should('have.text', 'This is the title of the basic post');
    cy.getElement('body').should('have.text', 'This is the body of the basic post');
  })

})
