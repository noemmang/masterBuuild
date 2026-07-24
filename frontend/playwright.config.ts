import { defineConfig, devices } from '@playwright/test';

// Este archivo va en /frontend (junto a angular.json y package.json)
export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  retries: process.env.CI ? 2 : 0,
  reporter: 'html',

  use: {
    baseURL: 'http://localhost:4200',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },

  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    // Descomenta si quieres probar en más navegadores:
    // { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    // { name: 'webkit', use: { ...devices['Desktop Safari'] } },
  ],

  // Playwright levanta ambos servidores antes de correr los tests
  // y los apaga al terminar (si no estaban ya corriendo).
  webServer: [
    {
      command: 'php artisan serve --port=8000',
      cwd: '../backend',
      url: 'http://localhost:8000',
      reuseExistingServer: !process.env.CI,
      timeout: 30_000,
    },
    {
      command: 'npm start',
      url: 'http://localhost:4200',
      reuseExistingServer: !process.env.CI,
      timeout: 60_000,
    },
  ],
});