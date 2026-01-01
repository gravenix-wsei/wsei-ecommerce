import { test, expect } from '@playwright/test';

/**
 * Admin Panel - Categories Section Tests
 * Tests for category management in the admin panel
 */

test.describe('Admin - Categories', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/admin/category');
  });

  test('should display categories list', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('Categories');

    const categoriesList = page.locator('table, [data-testid="categories-list"]');
    await expect(categoriesList).toBeVisible();
  });
});

