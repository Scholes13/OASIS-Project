import { test, expect, Page } from '@playwright/test';

/**
 * E2E Test: Purchasing Admin Assignment Parity
 *
 * Tests the full browser journey — same pattern as activity admin E2E:
 * 1. Super Admin → Purchasing Admins page loads
 * 2. Super Admin → toggle purchasing admin + report access ON for Yulia Eka
 * 3. Yulia Eka → verify Purchasing Admin dashboard accessible
 * 4. Yulia Eka → verify Consolidated Report accessible (report toggle ON)
 * 5. Super Admin → toggle admin OFF → report auto-revoked
 * 6. Yulia Eka → verify access revoked
 */

const SUPER_ADMIN = { email: 'super@werkudara.com', password: 'werkudara88' };
const YULIA_EKA = { email: 'eka@werkudara.com', password: 'werkudara88' };

async function login(page: Page, email: string, password: string) {
    await page.goto('/login');
    await page.waitForSelector('input[type="email"], input[name="email"]', { timeout: 15000 });
    await page.fill('input[type="email"], input[name="email"]', email);
    await page.fill('input[type="password"], input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 15000 });
}

async function goToPurchasingAdminsAndSearch(page: Page, searchName: string) {
    await page.goto('/admin/purchasing-admins');
    await page.waitForLoadState('networkidle');

    const searchInput = page.locator('input[type="text"], input[type="search"], input[placeholder*="earch"]').first();
    if (await searchInput.isVisible()) {
        await searchInput.fill(searchName);
        await page.waitForTimeout(1500);
        await page.keyboard.press('Enter');
        await page.waitForLoadState('networkidle');
    }
}

test.describe('Purchasing Admin Assignment Parity', () => {

    // ─────────────────────────────────────────────────────────
    // PHASE 1: Super Admin → page loads + toggle ON
    // ─────────────────────────────────────────────────────────
    test('Phase 1: Super Admin toggles Purchasing Admin + Report Access ON for Yulia Eka', async ({ page }) => {
        await login(page, SUPER_ADMIN.email, SUPER_ADMIN.password);

        // Verify page loads
        await page.goto('/admin/purchasing-admins');
        await page.waitForLoadState('networkidle');

        const hasTable = await page.locator('table').count();
        expect(hasTable).toBeGreaterThan(0);
        await page.screenshot({ path: 'tests/e2e/screenshots/p01-purchasing-admins-page.png', fullPage: true });

        // Search Yulia Eka
        await goToPurchasingAdminsAndSearch(page, 'Yulia Eka');
        const yukiaRow = page.locator('tr').filter({ hasText: 'Yulia Eka' }).first();
        expect(await yukiaRow.isVisible()).toBeTruthy();
        await page.screenshot({ path: 'tests/e2e/screenshots/p02-search-yulia-eka.png', fullPage: true });

        // Toggle Purchasing Admin ON if off
        const buttons = yukiaRow.locator('button');
        const adminToggle = buttons.first();
        const adminClasses = await adminToggle.getAttribute('class') || '';

        if (adminClasses.includes('bg-gray')) {
            await adminToggle.click();
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(1000);
            console.log('Toggled Purchasing Admin ON');
        } else {
            console.log('Purchasing Admin already ON');
        }

        await page.screenshot({ path: 'tests/e2e/screenshots/p03-admin-on.png', fullPage: true });

        // Toggle Report Access ON
        await goToPurchasingAdminsAndSearch(page, 'Yulia Eka');
        const yukiaRow2 = page.locator('tr').filter({ hasText: 'Yulia Eka' }).first();
        const buttons2 = yukiaRow2.locator('button');
        const buttonCount = await buttons2.count();
        console.log(`Button count: ${buttonCount}`);

        if (buttonCount >= 2) {
            const reportToggle = buttons2.nth(1);
            const isEnabled = await reportToggle.isEnabled();
            console.log(`Report toggle enabled: ${isEnabled}`);

            if (isEnabled) {
                const reportClasses = await reportToggle.getAttribute('class') || '';
                if (reportClasses.includes('bg-gray')) {
                    await reportToggle.click();
                    await page.waitForLoadState('networkidle');
                    await page.waitForTimeout(1000);
                    console.log('Toggled Report Access ON');
                } else {
                    console.log('Report Access already ON');
                }
            }
        }

        await page.screenshot({ path: 'tests/e2e/screenshots/p04-both-on.png', fullPage: true });

        // Verify final state
        await goToPurchasingAdminsAndSearch(page, 'Yulia Eka');
        const finalRow = page.locator('tr').filter({ hasText: 'Yulia Eka' }).first();
        const finalAdminClasses = await finalRow.locator('button').first().getAttribute('class') || '';
        expect(finalAdminClasses).not.toContain('bg-gray-200');
    });

    // ─────────────────────────────────────────────────────────
    // PHASE 2: Yulia Eka → Purchasing Admin dashboard
    // ─────────────────────────────────────────────────────────
    test('Phase 2: Yulia Eka can access Purchasing Admin dashboard', async ({ page }) => {
        await login(page, YULIA_EKA.email, YULIA_EKA.password);

        const response = await page.goto('/purchasing/admin/dashboard');
        await page.waitForLoadState('networkidle');

        const httpStatus = response?.status() ?? 0;
        const currentUrl = page.url();
        console.log(`Yulia Eka Purchasing Admin: url=${currentUrl}, status=${httpStatus}`);

        await page.screenshot({ path: 'tests/e2e/screenshots/p05-yulia-eka-purchasing-admin.png', fullPage: true });

        expect(httpStatus).toBeLessThan(400);
        expect(currentUrl).not.toContain('/login');
        console.log('PASS: Yulia Eka has Purchasing Admin access');
    });

    // ─────────────────────────────────────────────────────────
    // PHASE 3: Yulia Eka → Consolidated Report
    // ─────────────────────────────────────────────────────────
    test('Phase 3: Yulia Eka can access Consolidated Report', async ({ page }) => {
        await login(page, YULIA_EKA.email, YULIA_EKA.password);

        const response = await page.goto('/purchasing/admin/consolidated-report');
        await page.waitForLoadState('networkidle');

        const httpStatus = response?.status() ?? 0;
        const currentUrl = page.url();
        console.log(`Yulia Eka Consolidated Report: url=${currentUrl}, status=${httpStatus}`);

        await page.screenshot({ path: 'tests/e2e/screenshots/p06-yulia-eka-consolidated-report.png', fullPage: true });

        expect(httpStatus).toBeLessThan(400);
        expect(currentUrl).not.toContain('/login');
        console.log('PASS: Yulia Eka has Consolidated Report access');
    });

    // ─────────────────────────────────────────────────────────
    // PHASE 4: Super Admin → toggle OFF → auto-revoke
    // ─────────────────────────────────────────────────────────
    test('Phase 4: Super Admin turns OFF admin → report auto-revoked', async ({ page }) => {
        await login(page, SUPER_ADMIN.email, SUPER_ADMIN.password);
        await goToPurchasingAdminsAndSearch(page, 'Yulia Eka');

        const yukiaRow = page.locator('tr').filter({ hasText: 'Yulia Eka' }).first();
        const adminToggle = yukiaRow.locator('button').first();

        await adminToggle.click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        await page.screenshot({ path: 'tests/e2e/screenshots/p07-admin-off.png', fullPage: true });

        // Verify both OFF
        await goToPurchasingAdminsAndSearch(page, 'Yulia Eka');
        const finalRow = page.locator('tr').filter({ hasText: 'Yulia Eka' }).first();
        const finalButtons = finalRow.locator('button');

        const adminClasses = await finalButtons.first().getAttribute('class') || '';
        expect(adminClasses).toContain('bg-gray');
        console.log('Admin toggle is OFF');

        if (await finalButtons.count() >= 2) {
            const reportEnabled = await finalButtons.nth(1).isEnabled();
            expect(reportEnabled).toBeFalsy();
            console.log('Report toggle is disabled');
        }

        await page.screenshot({ path: 'tests/e2e/screenshots/p08-both-off.png', fullPage: true });
    });

    // ─────────────────────────────────────────────────────────
    // PHASE 5: Yulia Eka → access revoked
    // ─────────────────────────────────────────────────────────
    test('Phase 5: Yulia Eka access revoked after admin OFF', async ({ page }) => {
        await login(page, YULIA_EKA.email, YULIA_EKA.password);

        // Purchasing Admin dashboard should be blocked
        const adminResp = await page.goto('/purchasing/admin/dashboard');
        await page.waitForLoadState('networkidle');
        const adminStatus = adminResp?.status() ?? 0;
        const adminUrl = page.url();
        await page.screenshot({ path: 'tests/e2e/screenshots/p09-revoked-admin.png', fullPage: true });

        const adminBlocked = adminStatus >= 400 || adminUrl.includes('/login');
        console.log(`After revoke - Purchasing Admin: status=${adminStatus}, blocked=${adminBlocked}`);
        expect(adminBlocked).toBeTruthy();

        // Consolidated Report should also be blocked
        const reportResp = await page.goto('/purchasing/admin/consolidated-report');
        await page.waitForLoadState('networkidle');
        const reportStatus = reportResp?.status() ?? 0;
        const reportUrl = page.url();
        await page.screenshot({ path: 'tests/e2e/screenshots/p09-revoked-report.png', fullPage: true });

        const reportBlocked = reportStatus >= 400 || reportUrl.includes('/login');
        console.log(`After revoke - Consolidated Report: status=${reportStatus}, blocked=${reportBlocked}`);
        expect(reportBlocked).toBeTruthy();
    });
});
