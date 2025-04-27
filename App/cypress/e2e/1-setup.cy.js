describe('setup', function() {

  before(function() {
    cy.resetDatabase();
  })

  describe('setup admin', function() {

    beforeEach(function() {
      cy.fixture('setup').then((data) => {
        this.data = data.adminFormData;
      });
      cy.visit('/');
    })

    it('redirects to setup path', function() {
      cy.url().should('include', '/setup');
    })

    it('prevents form submission if no username is provided', function() {
      Object.keys(this.data).forEach((key) => {
        cy.getElement(key).type(this.data[key]);
      });
      cy.getElement('username').clear();
      cy.getElement('submit').click();
      cy.getElement('username').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('prevents form submission if no email address is provided', function() {
      Object.keys(this.data).forEach((key) => {
        cy.getElement(key).type(this.data[key]);
      });
      cy.getElement('email').clear();
      cy.getElement('submit').click();
      cy.getElement('email').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('prevents form submission if email address is invalid', function() {
      Object.keys(this.data).forEach((key) => {
        cy.getElement(key).type(this.data[key]);
      });
      cy.getElement('email').clear().type('example');
      cy.getElement('submit').click();
      cy.getElement('email').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.typeMismatch).to.be.true;
      })
    })

    it('prevents form submission if no password is provided', function() {
      Object.keys(this.data).forEach((key) => {
        cy.getElement(key).type(this.data[key]);
      });
      cy.getElement('password').clear();
      cy.getElement('submit').click();
      cy.getElement('password').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('prevents form submission if password is too weak', function() {
      Object.keys(this.data).forEach((key) => {
        cy.getElement(key).type(this.data[key]);
      });
      cy.getElement('password').clear().type('password');
      cy.getElement('submit').click();
      cy.getElement('password').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.patternMismatch).to.be.true;
      })
    })

    it('produces error if username uses non-alphanum characters', function() {
      Object.keys(this.data).forEach((key) => {
        cy.getElement(key).type(this.data[key]);
      });
      cy.getElement('username').clear().type('Username!');
      cy.getElement('submit').click();
      cy.getElement('invalidUsername').should('be.visible');
    })

    it('submits new admin form data', function() {
      Object.keys(this.data).forEach((key) => {
        cy.getElement(key).type(this.data[key]);
      });

      cy.getElement('submit').click();
      cy.url().should('include', '/setup/location');
    })

  })

  describe('setup location', function() {

    before(function() {
      cy.loadFixtureGroups(['setup-admin']);
    })

    beforeEach(function() {
      cy.visit('/');
    })

    it('redirects to the location path', function() {
      cy.url().should('include', '/setup/location');
    })

    it('displays a warning when radius is too small', function() {
      cy.getElement('sizeWarning').should('not.be.visible');
      cy.getElement('radius').clear().type('0.1');
      cy.getElement('kmsBtn').click();
      cy.getElement('sizeWarning').should('be.visible');
    })

    it('displays a warning when radius is too large', function() {
      cy.getElement('sizeWarning').should('not.be.visible');
      cy.getElement('radius').clear().type('1000');
      cy.getElement('kmsBtn').click();
      cy.getElement('sizeWarning').should('be.visible');
    })

    it('converts kilometers to miles value', function() {
      cy.getElement('kmsBtn').click();
      cy.getElement('radius').clear().type('5');
      cy.getElement('radius').should('have.value', '5');
      cy.getElement('milesBtn').click();
      cy.getElement('radius').should('have.value', '3.1');
    })

    it('converts miles to kilometers value', function() {
      cy.getElement('milesBtn').click();
      cy.getElement('radius').clear().type('5');
      cy.getElement('radius').should('have.value', '5');
      cy.getElement('kmsBtn').click();
      cy.getElement('radius').should('have.value', '8.0');
    })

    it('submits location form data', function() {
      cy.getElement('latLngInput').invoke('val', '51.5014, -0.1419');
      cy.getElement('radiusInput').invoke('val', '3000');
      cy.getElement('zoomInput').invoke('val', '3');
      cy.getElement('measurementInput').invoke('val', 'km');
      cy.getElement('submit').click();
      cy.visit('/');
      cy.url().should('include', '/setup/categories');
    })

  })

  describe('setup categories', function() {

    beforeEach(function() {
      cy.loadFixtureGroups(['setup-admin', 'setup-location']);
      cy.visit('/');
    })

    it('redirects to categories path', function() {
      cy.url().should('include', '/setup/categories');
    })

    it('prevents form submission when no categories are selected', function() {
      cy.getElement('submit').click();
      cy.url().should('include', '/setup/categories');
      cy.getElement('errors').should('be.visible');
    })

    it('submits categories form data', function() {
      cy.getElement('category').check();
      cy.getElement('submit').click();
      cy.visit('/');
      cy.url().should('include', '/setup/image-storage');
    })

  })

  describe('setup image storage', function() {

    beforeEach(function() {
      cy.loadFixtureGroups(['setup-admin', 'setup-location', 'setup-categories']);
      cy.visit('/');
    })

    it('redirects to image-storage path', function() {
      cy.url().should('include', '/setup/image-storage');
    })

  })
})
