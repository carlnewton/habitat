describe('heart', function() {

  before(function() {
    cy.loadFixtureGroups([
      'posts',
    ]);
  })

  beforeEach(function() {
    cy.then(Cypress.session.clearAllSavedSessions)
  })

  it('offers anonymous users to sign in when attempting to heart a post', function() {
    cy.visit('/');
    cy.getElement('heart').first().click();
    cy.getElement('modal-log-in').should('be.visible');
  })

  it('allows a user to heart a post', function() {
    cy.loginUser('neo');
    cy.visit('/');
    cy.getElement('heart').first().click();
    cy.getElement('heart-active').should('be.visible');
  })

  it('allows a user to unheart a post', function() {
    cy.loginUser('neo');
    cy.visit('/');
    cy.getElement('heart').first().click();
    cy.getElement('heart-active').should('be.visible');
    cy.getElement('heart-active').first().click();
    cy.getElement('heart').should('be.visible');
  })

  it('does not load the login modal for logged in users', function() {
    cy.visit('/');
    cy.getElement('modal-log-in').should('exist');
    cy.loginUser('neo');
    cy.getElement('modal-log-in').should('not.exist');
  })

  it('displays a sign in button conditionally', function() {
    cy.visit('/');
    cy.getElement('heart').first().click();
    cy.getElement('modal-log-in').should('be.visible');
    cy.getElement('modal-log-in-link').should('be.visible');
    cy.getElement('modal-sign-up-link').should('not.exist');

    cy.loginUser('admin');
    cy.visit('/admin/user-registration');
    cy.getElement('enable-registration-checkbox').check();
    cy.getElement('submit').click();
    cy.logoutUser();
    
    cy.getElement('heart').first().click();
    cy.getElement('modal-log-in').should('be.visible');
    cy.getElement('modal-log-in-link').should('be.visible');
    cy.getElement('modal-sign-up-link').should('be.visible');
  })

})
