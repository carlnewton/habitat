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

  describe('user moderation', function() {

    it('is not possible for administrator to promote self', function() {
      cy.loginUser('admin');
      cy.visit('/admin/moderation/users');
      cy.getElement('check-all').click();
      cy.getElement('actions').click();
      cy.getElement('promote').click();
      cy.getElement('btn-promote').click();
      cy.getElement('warning-message').contains('could not be promoted because they are the administrator');
    })

    it('is not possible for administrator to freeze self', function() {
      cy.loginUser('admin');
      cy.visit('/admin/moderation/users');
      cy.getElement('check-all').click();
      cy.getElement('actions').click();
      cy.getElement('freeze').click();
      cy.getElement('reason').type('Example reason');
      cy.getElement('btn-freeze').click();
      cy.getElement('warning-message').contains('could not be frozen because they are an administrator');
    })

    it('is not possible for administrator to ban self', function() {
      cy.loginUser('admin');
      cy.visit('/admin/moderation/users');
      cy.getElement('check-all').click();
      cy.getElement('actions').click();
      cy.getElement('ban').click();
      cy.getElement('reason').type('Example reason');
      cy.getElement('btn-ban').click();
      cy.getElement('warning-message').contains('could not be banned because they are an administrator');
    })
        
  })

})
