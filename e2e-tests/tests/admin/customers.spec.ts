import { test, expect } from '@playwright/test';

/**
 * Admin Panel - Customers Section Tests
 * Tests for customer management in the admin panel
 */

test.describe('Admin - Customers', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/admin/customer');
  });

  test('should display customers list', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('Customers');

    const customersList = page.locator('table, [data-testid="customers-list"]');
    await expect(customersList).toBeVisible();
  });
});

