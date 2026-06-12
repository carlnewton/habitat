describe('post', function() {

  before(function() {
    cy.loadFixtureGroups(['users']);
    cy.then(Cypress.session.clearAllSavedSessions)
  })

  beforeEach(function() {
    cy.loginUser('neo');
  })

  it('prevents a post without a title', function() {
    cy.visit('/post');
    cy.get('.tiptap').type('This is the body of the basic post')
    cy.getElement('submit').click();
    cy.getElement('title-errors').should('be.visible');
  })

  it('allows the creation of a basic post', function() {
    cy.visit('/post');
    cy.getElement('title').type('This is the title of the basic post');
    cy.get('.tiptap').type('This is the body of the basic post');
    cy.getElement('submit').click();
    cy.url().should('match', /\/post\/\d+$/)
    cy.getElement('success-message').should('be.visible');
    cy.getElement('title').should('have.text', 'This is the title of the basic post');
    cy.getElement('body').should('have.text', 'This is the body of the basic post');
  })

  it('allows the hyperlinks to be automatically added to the body of a post', function() {
    cy.visit('/post');
    cy.getElement('title').type('This is a post containing a hyperlink');
    cy.get('.tiptap').type('www.example.com - This is a domain');
    cy.getElement('submit').click();
    cy.url().should('match', /\/post\/\d+$/)
    cy.getElement('success-message').should('be.visible');
    cy.getElement('title').should('have.text', 'This is a post containing a hyperlink');
    cy.getElement('body').should('have.html', '<p><a href="http://www.example.com">www.example.com</a> - This is a domain</p>');
  })

  it('allows the hyperlinks to be manually added to the body of a post', function() {
    cy.visit('/post');
    cy.getElement('title').type('This is a post containing a hyperlink');
    cy.get('.tiptap').type('This contains a URL!{selectall}');
    cy.getElement('add-hyperlink').click();
    cy.getElement('hyperlink-url').type('https://www.example.com');
    cy.getElement('insert-hyperlink').click();
    cy.getElement('submit').click();
    cy.url().should('match', /\/post\/\d+$/)
    cy.getElement('success-message').should('be.visible');
    cy.getElement('title').should('have.text', 'This is a post containing a hyperlink');
    cy.getElement('body').should('have.html', '<p><a href="https://www.example.com">This contains a URL!</a></p>');
  })

})
