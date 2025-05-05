Cypress.Commands.add('resetDatabase', () => {
    cy.exec('docker exec habitat-apache-php php bin/console doctrine:database:drop --force --no-interaction', { failOnNonZeroExit: false });
    cy.exec('docker exec habitat-apache-php php bin/console doctrine:database:create --no-interaction');
    cy.exec('docker exec habitat-apache-php php bin/console doctrine:migrations:migrate --no-interaction');
});

Cypress.Commands.add('loadFixtureGroups', (fixtureGroups) => {
  let groups = '';
  fixtureGroups.forEach(async (fixtureGroup) => {
      groups += ' --group ' + fixtureGroup;
  });
  cy.exec(`docker exec habitat-apache-php php bin/console doctrine:fixtures:load ${groups} --no-interaction`);
});

Cypress.Commands.add('getElement', (dataTestValue) => {
  return cy.get(`[data-test="${dataTestValue}"]`);
});

Cypress.Commands.add('loginUser', (username) => {
  cy.then(Cypress.session.clearAllSavedSessions)
  cy.fixture('users').then((users) => {
    const user = users[username];
    cy.session(username, () => {
      cy.visit('/login')
      cy.getElement('email_address').type(user.email);
      cy.getElement('password').type(user.password);
      cy.getElement('submit').click();
      cy.url().should('not.include', 'login')
    });
  });
});

Cypress.Commands.add('logoutUser', () => {
  cy.visit('/logout')
  cy.url().should('not.include', 'logout')
  cy.then(Cypress.session.clearAllSavedSessions)
});

Cypress.Commands.add('switchToUser', (username) => {
  cy.logoutUser();
  cy.loginUser(username);
});

Cypress.Commands.add('createPost', (postReference) => {
  cy.fixture('posts').then((posts) => {
    const post = posts[postReference];
    cy.visit('/post');
    cy.getElement('title').type(post.title);
    cy.getElement('body').type(post.body);
    cy.getElement('submit').click();
    cy.url().should('match', /\/post\/\d+$/).then((url) => {
      const urlObject = new URL(url);
      const path = urlObject.pathname;
      const postId = path.split('/').pop();
      cy.wrap(postId).as('postId');
    })
  });
});

Cypress.Commands.add('addComment', (postId, comment) => {
  cy.visit('/post/' + postId);
  cy.getElement('commentFormBody').type(comment);
  cy.getElement('commentSubmit').click();
  cy.getElement('commentFormBody').should('not.have.value');
  cy.getElement('commentBody').should('include.text', comment);
  cy.getElement('commentErrors').should('not.exist');
});
