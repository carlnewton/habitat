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
