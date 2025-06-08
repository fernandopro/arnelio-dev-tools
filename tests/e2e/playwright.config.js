// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Dev-Tools E2E Testing Configuration - Arquitectura 3.0
 * Configuración avanzada para pruebas End-to-End con Playwright
 */
module.exports = defineConfig({
  // Directorio base para tests E2E
  testDir: './specs',
  
  // Timeout configuración
  timeout: 30 * 1000, // 30 segundos por test
  expect: {
    timeout: 5 * 1000, // 5 segundos para assertions
  },
  
  // Configuración de ejecución
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  
  // Reporter configuración
  reporter: [
    ['html', { outputFolder: '../coverage/html/e2e' }],
    ['json', { outputFile: '../coverage/json/e2e-results.json' }],
    ['junit', { outputFile: '../coverage/xml/e2e-results.xml' }],
    ['list']
  ],
  
  // Configuración global
  use: {
    // URL base (Local by Flywheel)
    baseURL: process.env.WP_SITE_URL || 'http://tarokina-2025.local',
    
    // Browser configuración
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    
    // Headers globales
    extraHTTPHeaders: {
      'Accept-Language': 'es-ES,es;q=0.9,en;q=0.8',
    },
    
    // Configuración viewport
    viewport: { width: 1280, height: 720 },
    
    // Timeout navegación
    navigationTimeout: 15 * 1000,
    actionTimeout: 10 * 1000,
  },

  // Configuración de proyectos (diferentes browsers)
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
    // Mobile testing
    {
      name: 'Mobile Chrome',
      use: { ...devices['Pixel 5'] },
    },
    {
      name: 'Mobile Safari',
      use: { ...devices['iPhone 12'] },
    },
  ],

  // Setup y teardown globales
  globalSetup: require.resolve('./setup/global-setup.js'),
  globalTeardown: require.resolve('./setup/global-teardown.js'),
  
  // Web server para desarrollo local
  webServer: process.env.CI ? undefined : {
    command: 'echo "Usando Local by Flywheel server"',
    url: process.env.WP_SITE_URL || 'http://tarokina-2025.local',
    reuseExistingServer: true,
    timeout: 10 * 1000,
  },

  // Configuración de output
  outputDir: '../coverage/e2e-artifacts',
});
