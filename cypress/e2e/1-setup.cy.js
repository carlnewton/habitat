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
        if (key !== 'username') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('submit').click();
      cy.getElement('username').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('prevents form submission if no email address is provided', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'email') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('submit').click();
      cy.getElement('email').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('prevents form submission if email address is invalid', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'email') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('email').type('example');
      cy.getElement('submit').click();
      cy.getElement('email').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.typeMismatch).to.be.true;
      })
    })

    it('prevents form submission if no password is provided', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'password') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('submit').click();
      cy.getElement('password').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('prevents form submission if password is too weak', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'password') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('password').type('password');
      cy.getElement('submit').click();
      cy.getElement('password').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.patternMismatch).to.be.true;
      })
    })

    it('produces error if username uses non-alphanum characters', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'username') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('username').type('Username!');
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

    before(function() {
      cy.loadFixtureGroups(['setup-admin', 'setup-location', 'setup-categories']);
    })

    beforeEach(function() {
      cy.fixture('setup').then((data) => {
        this.data = data.s3OptionsData;
      });
      cy.visit('/');
    })

    it('redirects to image-storage path', function() {
      cy.url().should('include', '/setup/image-storage');
    })

    it('displays s3 options when s3 radio button is clicked', function() {
      cy.getElement('s3').check();
      cy.getElement('s3Options').should('be.visible');
      cy.getElement('local').check();
      cy.getElement('s3Options').should('not.be.visible');
    })

    it('produces error if region is not selected', function() {
      cy.getElement('s3').check();
      Object.keys(this.data).forEach((key) => {
        cy.getElement(key).type(this.data[key]);
      });
      cy.getElement('submit').click();
      cy.getElement('regionErrors').should('be.visible');
    })

    it('produces error if bucket name is empty', function() {
      cy.getElement('s3').check();
      cy.getElement('region').select('eu-west-2');

      Object.keys(this.data).forEach((key) => {
        if (key !== 'bucketName') {
          cy.getElement(key).type(this.data[key]);
        }
      });

      cy.getElement('submit').click();
      cy.getElement('bucketNameErrors').should('be.visible');
    })

    it('produces error if access key is empty', function() {
      cy.getElement('s3').check();
      cy.getElement('region').select('eu-west-2');

      Object.keys(this.data).forEach((key) => {
        if (key !== 'accessKey') {
          cy.getElement(key).type(this.data[key]);
        }
      });

      cy.getElement('submit').click();
      cy.getElement('accessKeyErrors').should('be.visible');
    })

    it('produces error if secret key is empty', function() {
      cy.getElement('s3').check();
      cy.getElement('region').select('eu-west-2');

      Object.keys(this.data).forEach((key) => {
        if (key !== 'secretKey') {
          cy.getElement(key).type(this.data[key]);
        }
      });

      cy.getElement('submit').click();
      cy.getElement('secretKeyErrors').should('be.visible');
    })

    it('submits image storage form data for local storage', function() {
      cy.getElement('local').check();
      cy.getElement('submit').click();

      cy.url().should('include', '/setup/mail');
    })

  })

  describe('setup mail', function() {

    before(function() {
      cy.loadFixtureGroups([
        'setup-admin',
        'setup-location',
        'setup-categories',
        'setup-image-storage'
      ]);
    })

    beforeEach(function() {
      cy.fixture('setup').then((data) => {
        this.data = data.mailFormData;
      });
      cy.visit('/');
    })

    it('produces error if SMTP username is not populated', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'smtpUsername') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('submit').click();

      cy.getElement('smtpUsername').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('produces error if SMTP password is not populated', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'smtpPassword') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('submit').click();

      cy.getElement('smtpPassword').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('produces error if SMTP server is not populated', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'smtpServer') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('submit').click();

      cy.getElement('smtpServer').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('produces error if SMTP port is not populated', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'smtpPort') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('submit').click();

      cy.getElement('smtpPort').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('produces error if SMTP port is not numeric', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'smtpPort') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('smtpPort').type('non-numeric value');
      cy.getElement('submit').click();
      cy.getElement('smtpPortErrors').should('be.visible');
    })

    it('produces error if SMTP from email address is not populated', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'smtpFromEmailAddress') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('submit').click();

      cy.getElement('smtpFromEmailAddress').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('produces error if SMTP from email address is not valid', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'smtpFromEmailAddress') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('smtpFromEmailAddress').type('example');
      cy.getElement('submit').click();

      cy.getElement('smtpFromEmailAddress').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.typeMismatch).to.be.true;
      })
    })

    it('produces error if SMTP to email address is not valid', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'smtpToEmailAddress') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('smtpToEmailAddress').type('example');
      cy.getElement('submit').click();

      cy.getElement('smtpToEmailAddress').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.typeMismatch).to.be.true;
      })
    })

    it('submits mail form data', function() {
      Object.keys(this.data).forEach((key) => {
        if (key !== 'smtpToEmailAddress') {
          cy.getElement(key).type(this.data[key]);
        }
      });
      cy.getElement('submit').click();

      cy.url().should('include', '/admin');
    })

  })

})
