describe('comment', function() {

  beforeEach(function() {
    cy.resetDatabase();
    cy.loadFixtureGroups([
      'neo',
      'trinity',
    ]);
    cy.then(Cypress.session.clearAllSavedSessions)
  })

  it('does not allow a user to submit an empty comment', function() {
    cy.loginUser('neo');
    cy.createPost('basic');
    cy.getElement('success-message').should('be.visible');
    cy.getElement('commentErrors').should('not.exist');
    cy.getElement('commentFormBody').clear();
    cy.getElement('commentSubmit').click();
    cy.getElement('commentErrors').should('be.visible');
  })

  it('allows a user to comment on their own post', function() {
    cy.loginUser('neo');
    cy.createPost('basic');
    cy.get('@postId').then((postId) => {
      cy.visit('post/' + postId);
    })
    cy.getElement('commentFormBody').type('This is a comment');
    cy.getElement('commentSubmit').click();
    cy.getElement('commentFormBody').should('not.have.value');
    cy.getElement('commentBody').should('include.text', 'This is a comment');
    cy.getElement('commentErrors').should('not.exist');
  })

  it('allows a user to comment on another user post', function() {
    cy.loginUser('neo');
    cy.createPost('basic');
    cy.switchToUser('trinity');
    cy.get('@postId').then((postId) => {
      cy.addComment(postId, 'This is a comment from another user');
    })
  })

  it('displays a notification for new comments to the user who posted', function() {
    cy.loginUser('neo');
    cy.createPost('basic');
    cy.switchToUser('trinity');
    cy.get('@postId').then((postId) => {
      cy.addComment(postId, 'This is a comment from another user');
      cy.addComment(postId, 'This is another comment from another user');
    })
    cy.switchToUser('neo');
    cy.visit('/');
    cy.getElement('new-notifications-indicator').should('be.visible');
    cy.getElement('notifications').should('include.text', 'You have 2 new comments');
  })

})
