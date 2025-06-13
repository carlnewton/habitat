describe('user', function() {

  before(function() {
    cy.resetDatabase();
    cy.loadFixtureGroups(['setup']);
  })

  describe('user registration disabled', function() {
    
    it('does not display a sign up link', function() {
      cy.visit('/');
      cy.getElement('navbar').should('not.include.text', 'Sign up');
    })

    it('displays a registrations disabled message', function() {
      cy.visit('/signup');
      cy.url().should('not.match', /signup/);
      cy.getElement('warning-message').contains('Registrations are currently disabled');
    })

  })

  describe('user registration enabled', function() {
    
    before(function() {
      cy.loginUser('admin');
      cy.visit('/admin/user-registration');
      cy.getElement('enable-registration-checkbox').check();
      cy.getElement('submit').click();
      cy.logoutUser();
    })

    it('displays a sign up link', function() {
      cy.visit('/');
      cy.getElement('navbar').should('include.text', 'Sign up');
    })

    it('does not allow sign up with an empty username', function() {
      cy.visit('/signup');
      cy.getElement('email').type('test@example.com');
      cy.getElement('password').type('Password123');
      cy.getElement('submit').click();

      cy.getElement('username').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('does not allow sign up with an empty email address', function() {
      cy.visit('/signup');
      cy.getElement('username').type('username');
      cy.getElement('password').type('Password123');
      cy.getElement('submit').click();

      cy.getElement('email').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('does not allow sign up with an empty password', function() {
      cy.visit('/signup');
      cy.getElement('username').type('username');
      cy.getElement('email').type('test@example.com');
      cy.getElement('submit').click();

      cy.getElement('password').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.valueMissing).to.be.true;
      })
    })

    it('does not allow sign up with punctuation', function() {
      cy.visit('/signup');
      cy.getElement('username').type('user.name');
      cy.getElement('email').type('test@example.com');
      cy.getElement('password').type('Password123');
      cy.getElement('submit').click();

      cy.getElement('username-errors').should('be.visible');
    })

    it('does not allow sign up with invalid email address', function() {
      cy.visit('/signup');
      cy.getElement('username').type('username');
      cy.getElement('email').type('invalid email address');
      cy.getElement('password').type('Password123');
      cy.getElement('submit').click();

      cy.getElement('email').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.typeMismatch).to.be.true;
      })
    })

    it('does not allow sign up with lowercase only password', function() {
      cy.visit('/signup');
      cy.getElement('username').type('username');
      cy.getElement('email').type('test@example.com');
      cy.getElement('password').type('password');
      cy.getElement('submit').click();

      cy.getElement('password').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.patternMismatch).to.be.true;
      })
    })

    it('does not allow sign up with uppercase only password', function() {
      cy.visit('/signup');
      cy.getElement('username').type('username');
      cy.getElement('email').type('test@example.com');
      cy.getElement('password').type('PASSWORD');
      cy.getElement('submit').click();

      cy.getElement('password').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.patternMismatch).to.be.true;
      })
    })

    it('does not allow sign up with alphabetic only password', function() {
      cy.visit('/signup');
      cy.getElement('username').type('username');
      cy.getElement('email').type('test@example.com');
      cy.getElement('password').type('Password');
      cy.getElement('submit').click();

      cy.getElement('password').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.patternMismatch).to.be.true;
      })
    })

    it('does not allow sign up with numeric only password', function() {
      cy.visit('/signup');
      cy.getElement('username').type('username');
      cy.getElement('email').type('test@example.com');
      cy.getElement('password').type('123456789');
      cy.getElement('submit').click();

      cy.getElement('password').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.patternMismatch).to.be.true;
      })
    })

    it('does not allow sign up with short password', function() {
      cy.visit('/signup');
      cy.getElement('username').type('username');
      cy.getElement('email').type('test@example.com');
      cy.getElement('password').type('Pass123');
      cy.getElement('submit').click();

      cy.getElement('password').invoke('prop', 'validity').then((validity) => {
        expect(validity.valid).to.be.false;
        expect(validity.patternMismatch).to.be.true;
      })
    })

    it('does not allow sign up with existing username (case insensitive)', function() {
      cy.visit('/signup');
      cy.getElement('username').type('ArChItEcT');
      cy.getElement('email').type('test@example.com');
      cy.getElement('password').type('Password123');
      cy.getElement('submit').click();

      cy.getElement('username-errors').should('be.visible');
    })

    it('allows sign up', function() {
      cy.visit('/signup');
      cy.getElement('username').type('username');
      cy.getElement('email').type('test@example.com');
      cy.getElement('password').type('Password123');
      cy.getElement('submit').click();

      cy.getElement('success-message').contains('Check your emails to verify your email address');
    })

    it('allows sign up with spaces around username', function() {
      cy.visit('/signup');
      cy.getElement('username').type('  SpacesAroundUsername  ');
      cy.getElement('email').type('SpacesAroundUsername@example.com');
      cy.getElement('password').type('Password123');
      cy.getElement('submit').click();

      cy.getElement('success-message').contains('Check your emails to verify your email address');
    })

    it('allows sign up with spaces around email address', function() {
      cy.visit('/signup');
      cy.getElement('username').type('SpacesAroundEmailAddress');
      cy.getElement('email').type('  SpacesAroundEmailAddress@example.com  ');
      cy.getElement('password').type('Password123');
      cy.getElement('submit').click();

      cy.getElement('success-message').contains('Check your emails to verify your email address');
    })

    it('feigns sign up with existing email address', function() {
      cy.visit('/signup');
      cy.getElement('username').type('AlreadyExistingEmailAddress');
      cy.getElement('email').type('architect@example.com');
      cy.getElement('password').type('Password123');
      cy.getElement('submit').click();

      cy.getElement('success-message').contains('Check your emails to verify your email address');
    })

    it('allows sign up with secure password', function() {
      cy.visit('/signup');
      cy.getElement('username').type('LongPassword');
      cy.getElement('email').type('LongPassword@example.com');
      cy.getElement('password').type('[k[svB@<$q4bWN5[VO&X9-=@!P[.RH<2")(b}"qbT£TQ3KkEsp');
      cy.getElement('submit').click();

      cy.getElement('success-message').contains('Check your emails to verify your email address');
    })

    it('does not allow sign in when unverified', function() {
      cy.visit('/signup');
      cy.getElement('username').type('UnverifiedUser');
      cy.getElement('email').type('UnverifiedUser@example.com');
      cy.getElement('password').type('Password123');
      cy.getElement('submit').click();

      cy.getElement('success-message').contains('Check your emails to verify your email address');

      cy.visit('/login')
      cy.getElement('email_address').type('UnverifiedUser@example.com');
      cy.getElement('password').type('Password123');
      cy.getElement('submit').click();

      cy.getElement('email-verification-failed').should('be.visible');
    })

  })

  describe('user signed up', function() {

    before(function() {
      cy.resetDatabase();
      cy.loadFixtureGroups(['users']);
    })

    it('allows a user to change their username', function() {
      cy.loginUser('neo');
      cy.visit('/settings');
      cy.getElement('change-username').clear().type('One');
      cy.getElement('change-username-submit').click();

      cy.getElement('change-username-success').should('be.visible');
    })

    it('allows a user to change their username with spaces around', function() {
      cy.loginUser('neo');
      cy.visit('/settings');
      cy.getElement('change-username').clear().type('  Two  ');
      cy.getElement('change-username-submit').click();

      cy.getElement('change-username').should('have.value', 'Two')
      cy.getElement('change-username-success').should('be.visible');
    })

    it('does not allow a user to change their username to something to something short', function() {
      cy.loginUser('neo');
      cy.visit('/settings');
      cy.getElement('change-username').clear().type('Io');
      cy.getElement('change-username-submit').click();

      cy.getElement('change-username-errors').should('be.visible');
    })

    it('does not allow a user to change their username to something to something long', function() {
      cy.loginUser('neo');
      cy.visit('/settings');
      cy.getElement('change-username').clear().type('ThisIsAUsernameThatIsFarTooLong');
      cy.getElement('change-username-submit').click();

      cy.getElement('change-username-errors').should('be.visible');
    })

  })

})
