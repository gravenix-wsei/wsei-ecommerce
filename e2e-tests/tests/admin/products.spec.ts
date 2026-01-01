import { test, expect } from '@playwright/test';

/**
 * Admin Panel - Products Section Tests
 * Tests for product management in the admin panel
 */

test.describe('Admin - Products', () => {
  test.use({ storageState: 'playwright/.auth/admin.json' });
  test.beforeEach(async ({ page }) => {
    // Navigate to products section
    await page.goto('/admin/product');
  });


  test('should display products list', async ({ page }) => {
    // Verify page title or heading
    await expect(page.locator('.admin-header > h1')).toContainText('Products');

    // Verify table or product grid exists
    const productsList = page.locator('table, [data-testid="products-list"]');
    await expect(productsList).toBeVisible();
  });

  test('should navigate to create product page', async ({ page }) => {
    // Click "Add Product" or "Create" button
    await page.click('a:has-text("Add Product")');

    // Verify we're on the create page
    await expect(page).toHaveURL('admin/product/new');
    await expect(page.locator('h1')).toContainText(/Add|Create|New/);
  });
});

