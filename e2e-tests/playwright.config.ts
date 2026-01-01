import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';
import path from 'path';

/**
 * Load environment variables for E2E tests
 */
dotenv.config({ path: path.resolve(__dirname, '.env') });

/**
 * Playwright configuration for WSEI E-commerce application
 * Tests the Symfony-based e-commerce platform including admin panel
 *
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './tests',

  /* Maximum time one test can run - increased for e-commerce DB operations */
  timeout: 30 * 1000,

  /* Maximum time to wait for expect() assertions */
  expect: {
    timeout: 5000,
  },

  /* Run tests in files in parallel */
  fullyParallel: true,

  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,

  /* Retry on CI only - e-commerce apps can have flaky tests due to DB state */
  retries: process.env.CI ? 2 : 0,

  /* Opt out of parallel tests on CI to avoid DB conflicts */
  workers: process.env.CI ? 1 : undefined,

  /* Reporter configuration - HTML report + GitHub Actions reporter in CI */
  reporter: process.env.CI
    ? [['html'], ['github']]
    : [['html'], ['list']],

  /* Output directory for test artifacts */
  outputDir: 'test-results/',

  /* Shared settings for all the projects below */
  use: {
    /* Base URL for the Symfony application - matches nginx port from compose.yml */
    baseURL: process.env.BASE_URL || 'http://localhost:8080',

    /* Collect trace when retrying the failed test */
    trace: 'on-first-retry',

    /* Screenshot on failure for debugging */
    screenshot: 'only-on-failure',

    /* Video on first retry to debug flaky tests */
    video: 'retain-on-failure',

    /* Set locale to match business requirements */
    locale: 'pl-PL',

    /* Set timezone to match server */
    timezoneId: 'Europe/Warsaw',
  },

  /* Configure projects for major browsers */
  projects: [
    // Setup project for authentication state - runs before all tests
    {
      name: 'setup',
      testMatch: /.*\.setup\.ts/,
      testDir: './tests',
    },

    // Main desktop browser - Chromium (fastest, most stable)
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        // Storage state from setup for authenticated tests
        storageState: 'playwright/.auth/admin.json',
      },
      dependencies: ['setup'],
    },

    // Firefox - enable only when needed or in CI
    // {
    //   name: 'firefox',
    //   use: {
    //     ...devices['Desktop Firefox'],
    //     storageState: 'playwright/.auth/admin.json',
    //   },
    //   dependencies: ['setup'],
    // },

    // Safari/Webkit - enable only when needed or in CI
    // {
    //   name: 'webkit',
    //   use: {
    //     ...devices['Desktop Safari'],
    //     storageState: 'playwright/.auth/admin.json',
    //   },
    //   dependencies: ['setup'],
    // },

    /* Test against mobile viewports for responsive e-commerce */
    // {
    //   name: 'Mobile Chrome',
    //   use: {
    //     ...devices['Pixel 5'],
    //     storageState: 'playwright/.auth/admin.json',
    //   },
    //   dependencies: ['setup'],
    // },
    // {
    //   name: 'Mobile Safari',
    //   use: {
    //     ...devices['iPhone 12'],
    //     storageState: 'playwright/.auth/admin.json',
    //   },
    //   dependencies: ['setup'],
    // },
  ],

  /*
   * Web server validation
   * Note: Docker containers should be started manually via 'make up'
   * Playwright will check if the URL is accessible before running tests
   * Set reuseExistingServer: true since we're using Docker, not starting a server
   */
  // webServer: {
  //   url: 'http://localhost:8080',
  //   timeout: 10 * 1000,
  //   reuseExistingServer: true,
  // },
});
