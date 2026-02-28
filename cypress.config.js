const { defineConfig } = require("cypress");

module.exports = defineConfig({
  e2e: {
    baseUrl: 'https://localhost',
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
  },
});
