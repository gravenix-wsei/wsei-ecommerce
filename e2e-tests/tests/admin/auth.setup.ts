import { test as setup, expect } from '@playwright/test';
import path from 'path';

/**
 * Authentication setup for admin users
 * This runs once before all tests and saves the authentication state
 * Tests can then reuse this state to avoid repeated login
 */

const authFile = path.join(__dirname, '../../playwright/.auth/admin.json');

setup('authenticate as admin', async ({ page }) => {
  const adminEmail = process.env.ADMIN_EMAIL || 'admin@example.com';
  const adminPassword = process.env.ADMIN_PASSWORD || 'admin123';

  console.log('Starting admin authentication...');
  console.log('Admin email:', adminEmail);

  // Navigate to admin login page
  await page.goto('/admin/login');
  console.log('Navigated to login page');

  // Wait for the form to be visible
  await page.waitForSelector('input[name="_username"]', { state: 'visible' });
  await page.waitForSelector('input[name="_password"]', { state: 'visible' });
  console.log('Login form is visible');

  // Perform authentication steps
  await page.fill('input[name="_username"]', adminEmail);
  await page.fill('input[name="_password"]', adminPassword);
  console.log('Filled in credentials');

  // Click submit and wait for navigation
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.click('button[type="submit"]'),
  ]);
  console.log('Form submitted, navigation completed');

  // Wait for successful login - should redirect to dashboard
  await page.waitForURL(/\/admin(?!\/login)/, { timeout: 10000 });
  console.log('Successfully redirected after login. Current URL:', page.url());

  // Verify we're logged in by checking for admin-specific element
  // The admin panel has a sidebar with class .admin-sidebar
  const adminSidebar = page.locator('.admin-sidebar');
  await expect(adminSidebar).toBeVisible({ timeout: 5000 });
  console.log('Admin panel sidebar is visible');

  // Save authentication state
  await page.context().storageState({ path: authFile });
  console.log('Authentication state saved to:', authFile);
});

