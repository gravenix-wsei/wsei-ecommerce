import { test, expect } from '@playwright/test';

/**
 * Admin Panel - Settings Section Tests
 * Tests for application settings and configuration
 */

test.describe('Admin - Settings', () => {
  test.use({ storageState: 'playwright/.auth/admin.json' });

  test('should require proper permissions to access settings', async ({ page }) => {
    await page.goto('/admin/settings');
    await expect(page.locator('h1')).toContainText('Settings');
  });

  test('should create admin user with limited permissions and verify menu visibility', async ({ page }) => {
    // Generate unique email for the new admin user
    const timestamp = Date.now();
    const newAdminEmail = `admin-limited-${timestamp}@example.com`;
    const newAdminPassword = 'SecurePass123!';

    // Step 1: Navigate to Admin Users settings as super admin
    await page.goto('/admin/settings/admin-users');
    await expect(page.locator('h1')).toContainText('Admin Users');

    // Step 2: Click "Add New Admin" button
    await page.click('a.btn-primary:has-text("Add New Admin")');
    await expect(page.locator('h1')).toContainText('Add New Admin');

    // Step 3: Fill in the new admin user form
    await page.fill('input[name="admin_user[email]"]', newAdminEmail);
    await page.fill('input[name="admin_user[plainPassword]"]', newAdminPassword);

    // Step 4: Select only Customer Manager and Order Manager roles
    // First, uncheck all roles that might be selected by default
    await page.uncheck('input[type="checkbox"][value="ROLE_ADMIN.PRODUCT"]');
    await page.uncheck('input[type="checkbox"][value="ROLE_ADMIN.CATEGORY"]');
    await page.uncheck('input[type="checkbox"][value="ROLE_ADMIN.CONFIG"]');
    await page.uncheck('input[type="checkbox"][value="ROLE_SUPER_ADMIN"]');

    // Check only the Customer and Order management roles
    await page.check('input[type="checkbox"][value="ROLE_ADMIN.CUSTOMER"]');
    await page.check('input[type="checkbox"][value="ROLE_ADMIN.ORDER"]');

    // Step 5: Submit the form
    await page.click('button[type="submit"]:has-text("Create Administrator")');

    // Wait for success message or redirect to admin users list
    await expect(page.locator('.flash-success, .alert-success')).toContainText('successfully', { timeout: 5000 });

    // Step 6: Logout from current admin account
    await page.goto('/admin/logout');
    await page.waitForURL(/\/admin\/login/, { timeout: 5000 });

    // Step 7: Login as the newly created admin user
    await page.fill('input[name="_username"]', newAdminEmail);
    await page.fill('input[name="_password"]', newAdminPassword);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"]'),
    ]);

    // Wait for successful login - should redirect to dashboard
    await page.waitForURL(/\/admin(?!\/login)/, { timeout: 10000 });

    // Step 8: Verify the admin panel menu structure
    const adminSidebar = page.locator('.admin-sidebar');
    await expect(adminSidebar).toBeVisible();

    // Verify Dashboard is visible (always visible to all admins)
    const dashboardLink = page.locator('.admin-menu-link:has(.admin-menu-text:text("Dashboard"))');
    await expect(dashboardLink).toBeVisible();

    // Verify Customers is visible (ROLE_ADMIN.CUSTOMER)
    const customersLink = page.locator('.admin-menu-link:has(.admin-menu-text:text("Customers"))');
    await expect(customersLink).toBeVisible();

    // Verify Orders is visible (ROLE_ADMIN.ORDER)
    const ordersLink = page.locator('.admin-menu-link:has(.admin-menu-text:text("Orders"))');
    await expect(ordersLink).toBeVisible();

    // Verify Categories is NOT visible (ROLE_ADMIN.CATEGORY not assigned)
    const categoriesLink = page.locator('.admin-menu-link:has(.admin-menu-text:text("Categories"))');
    await expect(categoriesLink).not.toBeVisible();

    // Verify Products is NOT visible (ROLE_ADMIN.PRODUCT not assigned)
    const productsLink = page.locator('.admin-menu-link:has(.admin-menu-text:text("Products"))');
    await expect(productsLink).not.toBeVisible();

    // Verify Settings is NOT visible (ROLE_ADMIN.CONFIG not assigned)
    const settingsLink = page.locator('.admin-menu-link:has(.admin-menu-text:text("Settings"))');
    await expect(settingsLink).not.toBeVisible();

    // Step 9: Verify we can access allowed sections
    await customersLink.click();
    await expect(page.locator('h1')).toContainText('Customers');

    await ordersLink.click();
    await expect(page.locator('h1')).toContainText('Orders');

    // Step 10: Verify we cannot access restricted sections by direct URL
    await page.goto('/admin/product');
    // Should be redirected or see access denied
    await expect(page.locator('body')).toContainText(/Access Denied|403|Forbidden/i);

    await page.goto('/admin/category');
    await expect(page.locator('body')).toContainText(/Access Denied|403|Forbidden/i);

    await page.goto('/admin/settings');
    await expect(page.locator('body')).toContainText(/Access Denied|403|Forbidden/i);

    // Step 11: Cleanup - Logout from limited admin and login as super admin to delete the test user
    await page.goto('/admin/logout');
    await page.waitForURL(/\/admin\/login/, { timeout: 5000 });

    // Login as super admin again
    const superAdminEmail = process.env.ADMIN_EMAIL || 'admin@example.com';
    const superAdminPassword = process.env.ADMIN_PASSWORD || 'admin123';

    await page.fill('input[name="_username"]', superAdminEmail);
    await page.fill('input[name="_password"]', superAdminPassword);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"]'),
    ]);

    // Navigate to admin users list
    await page.goto('/admin/settings/admin-users');
    await expect(page.locator('h1')).toContainText('Admin Users');

    // Find and delete the created test user
    const userRow = page.locator(`tr:has-text("${newAdminEmail}")`);
    await expect(userRow).toBeVisible();

    // Set up dialog handler for confirmation
    page.on('dialog', dialog => dialog.accept());

    // Click delete button (will trigger confirmation dialog)
    await userRow.locator('button.btn-danger[type="submit"]').click();

    // Wait for the page to reload after deletion
    await page.waitForURL('/admin/settings/admin-users', { timeout: 5000 });

    // Verify the user is no longer in the list
    await expect(page.locator(`tr:has-text("${newAdminEmail}")`)).not.toBeVisible();
  });
});

