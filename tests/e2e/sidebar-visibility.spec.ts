import { test, expect, Page } from '@playwright/test';

/**
 * E2E Test: Sidebar Menu Visibility
 *
 * Verifies that admin menu items appear/disappear in the sidebar
 * based on user flags. Tests real browser rendering, not API.
 *
 * 1. Yulia Eka (flags OFF) → sidebar should NOT show Activity Admin / Purchasing Admin
 * 2. Super Admin toggles Activity Admin ON → Yulia Eka sidebar shows Activity Admin
 * 3. Super Admin toggles Purchasing Admin ON → Yulia Eka sidebar shows Purchasing Admin
 * 4. Super Admin toggles both OFF → Yulia Eka sidebar hides both
 */

const SUPER_ADMIN = { email: 'super@werkudara.com', password: 'werkudara88' };
const YULIA_EKA = { email: 'eka@werkudara.com', password: 'werkudara88' };

async function login(page: Page, email: string, password: string) {
    // Logout first if already logged in (clear session)
    await page.context().clearCookies();
    await page.goto('/login');
    await page.waitForSelector('input[type="email"], input[name="email"]', { timeout: 15000 });
    await page.fill('input[type="email"], input[name="email"]', email);
    await page.fill('input[type="password"], input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 15000 });
}

async function toggleFlag(page: Page, adminPage: string, searchName: string, buttonIndex: number) {
    await page.goto(adminPage);
    await page.waitForLoadState('networkidle');

    const searchInput = page.locator('input[type="text"], input[type="search"], input[placeholder*="earch"]').first();
    if (await searchInput.isVisible()) {
        await searchInput.fill(searchName);
        await page.waitForTimeout(1500);
        await page.keyboard.press('Enter');
        await page.waitForLoadState('networkidle');
    }

    const row = page.locator('tr').filter({ hasText: searchName }).first();
    if (await row.isVisible()) {
        const btn = row.locator('button').nth(buttonIndex);
        await btn.click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
    }
}

/** Check if sidebar contains a link with given text */
async function sidebarHasLink(page: Page, text: string): Promise<boolean> {
    // Sidebar is typically a nav or aside element; search broadly for link text
    const sidebar = page.locator('nav, aside, [data-sidebar]');
    const allText = await sidebar.allTextContents();
    const combined = allText.join(' ');
    return combined.includes(text);
}

test.describe('Sidebar Menu Visibility', () => {

    // ─────────────────────────────────────────────────────────
    // PHASE 1: Yulia Eka (all flags OFF) → no admin menus
    // ─────────────────────────────────────────────────────────
    test('Phase 1: Yulia Eka sees NO admin menus when flags are OFF', async ({ page }) => {
        await login(page, YULIA_EKA.email, YULIA_EKA.password);

        // Go to dashboard (main page with sidebar)
        await page.goto('/activity/dashboard');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        await page.screenshot({ path: 'tests/e2e/screenshots/s01-yulia-sidebar-flags-off.png', fullPage: true });

        const bodyText = await page.textContent('body') || '';

        // Check sidebar for admin menu items by text
        // "Activity Admin" and "Purchasing Admin" are sidebar menu item labels
        const sidebarArea = page.locator('nav, aside, [data-sidebar], .sidebar');
        const sidebarText = await sidebarArea.allTextContents().then(t => t.join(' '));

        const hasActivityAdmin = sidebarText.includes('Activity Admin');
        const hasPurchasingAdmin = sidebarText.includes('Purchasing Admin');

        console.log(`Flags OFF - sidebar has 'Activity Admin': ${hasActivityAdmin}`);
        console.log(`Flags OFF - sidebar has 'Purchasing Admin': ${hasPurchasingAdmin}`);

        expect(hasActivityAdmin).toBeFalsy();
        expect(hasPurchasingAdmin).toBeFalsy();
    });

    // ─────────────────────────────────────────────────────────
    // PHASE 2: Super Admin toggles Activity Admin ON → sidebar shows it
    // ─────────────────────────────────────────────────────────
    test('Phase 2: After Activity Admin ON, Yulia Eka sees Activity Admin menu', async ({ page }) => {
        // Super Admin toggles ON
        await login(page, SUPER_ADMIN.email, SUPER_ADMIN.password);
        await toggleFlag(page, '/admin/activity-admins', 'Yulia Eka', 0); // toggle admin ON
        console.log('Toggled Activity Admin ON for Yulia Eka');

        // Login as Yulia Eka
        await login(page, YULIA_EKA.email, YULIA_EKA.password);

        await page.goto('/activity/dashboard');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        await page.screenshot({ path: 'tests/e2e/screenshots/s02-yulia-sidebar-activity-on.png', fullPage: true });

        // Should see "Activity Admin" text in sidebar
        const sidebarText = await page.locator('nav, aside, [data-sidebar], .sidebar').allTextContents().then(t => t.join(' '));
        const hasActivityAdmin = sidebarText.includes('Activity Admin');
        console.log(`Activity Admin ON - visible in sidebar: ${hasActivityAdmin}`);

        expect(hasActivityAdmin).toBeTruthy();
    });

    // ─────────────────────────────────────────────────────────
    // PHASE 3: Super Admin toggles Purchasing Admin ON → sidebar shows it
    // ─────────────────────────────────────────────────────────
    test('Phase 3: After Purchasing Admin ON, Yulia Eka sees Purchasing Admin menu', async ({ page }) => {
        // Super Admin toggles ON
        await login(page, SUPER_ADMIN.email, SUPER_ADMIN.password);
        await toggleFlag(page, '/admin/purchasing-admins', 'Yulia Eka', 0); // toggle admin ON
        console.log('Toggled Purchasing Admin ON for Yulia Eka');

        // Login as Yulia Eka
        await login(page, YULIA_EKA.email, YULIA_EKA.password);

        await page.goto('/activity/dashboard');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        await page.screenshot({ path: 'tests/e2e/screenshots/s03-yulia-sidebar-both-on.png', fullPage: true });

        // Should see both admin menus in sidebar
        const sidebarText = await page.locator('nav, aside, [data-sidebar], .sidebar').allTextContents().then(t => t.join(' '));

        const hasPurchasingAdmin = sidebarText.includes('Purchasing Admin');
        console.log(`Purchasing Admin ON - visible in sidebar: ${hasPurchasingAdmin}`);
        expect(hasPurchasingAdmin).toBeTruthy();

        const hasActivityAdmin = sidebarText.includes('Activity Admin');
        console.log(`Activity Admin still visible: ${hasActivityAdmin}`);
        expect(hasActivityAdmin).toBeTruthy();
    });

    // ─────────────────────────────────────────────────────────
    // PHASE 4: Super Admin toggles both OFF → sidebar hides both
    // ─────────────────────────────────────────────────────────
    test('Phase 4: After both OFF, Yulia Eka sees NO admin menus', async ({ page }) => {
        await login(page, SUPER_ADMIN.email, SUPER_ADMIN.password);

        // Toggle Activity Admin OFF
        await toggleFlag(page, '/admin/activity-admins', 'Yulia Eka', 0);
        console.log('Toggled Activity Admin OFF');

        // Toggle Purchasing Admin OFF
        await toggleFlag(page, '/admin/purchasing-admins', 'Yulia Eka', 0);
        console.log('Toggled Purchasing Admin OFF');

        // Login as Yulia Eka
        await login(page, YULIA_EKA.email, YULIA_EKA.password);

        await page.goto('/activity/dashboard');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        await page.screenshot({ path: 'tests/e2e/screenshots/s04-yulia-sidebar-both-off.png', fullPage: true });

        const sidebarText = await page.locator('nav, aside, [data-sidebar], .sidebar').allTextContents().then(t => t.join(' '));

        const hasActivityAdmin = sidebarText.includes('Activity Admin');
        const hasPurchasingAdmin = sidebarText.includes('Purchasing Admin');

        console.log(`Both OFF - sidebar has 'Activity Admin': ${hasActivityAdmin}`);
        console.log(`Both OFF - sidebar has 'Purchasing Admin': ${hasPurchasingAdmin}`);

        expect(hasActivityAdmin).toBeFalsy();
        expect(hasPurchasingAdmin).toBeFalsy();
    });
});
