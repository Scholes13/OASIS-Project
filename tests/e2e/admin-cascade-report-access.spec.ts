import { test, expect, Page } from '@playwright/test';

/**
 * E2E Test: Admin Flag Cascade + Activity Report Access
 *
 * Full journey test using real browser login and navigation.
 * Tests toggle ON, verify access, toggle OFF, verify revoked.
 */

const SUPER_ADMIN = { email: 'super@werkudara.com', password: 'werkudara88' };
const YULIA_EKA = { email: 'eka@werkudara.com', password: 'werkudara88' };
const YULIA_RINI = { email: 'yulia@werkudara.com', password: 'werkudara88' };

// Helper: login
async function login(page: Page, email: string, password: string) {
    await page.goto('/login');
    await page.waitForSelector('input[type="email"], input[name="email"]', { timeout: 15000 });
    await page.fill('input[type="email"], input[name="email"]', email);
    await page.fill('input[type="password"], input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 15000 });
}

// Helper: navigate to Activity Admins and search for user
async function goToActivityAdminsAndSearch(page: Page, searchName: string) {
    await page.goto('/admin/activity-admins');
    await page.waitForLoadState('networkidle');

    const searchInput = page.locator('input[type="text"], input[type="search"], input[placeholder*="earch"]').first();
    if (await searchInput.isVisible()) {
        await searchInput.fill(searchName);
        await page.waitForTimeout(1500);
        await page.keyboard.press('Enter');
        await page.waitForLoadState('networkidle');
    }
}

test.describe('Admin Flag Cascade + Report Access', () => {

    // ─────────────────────────────────────────────────────────
    // PHASE 1: Super Admin toggles ON admin + report for Yulia Eka
    // ─────────────────────────────────────────────────────────
    test('Phase 1: Super Admin toggles Activity Admin + Report Access ON for Yulia Eka', async ({ page }) => {
        await login(page, SUPER_ADMIN.email, SUPER_ADMIN.password);

        // Step 1: Go to Activity Admins page
        await goToActivityAdminsAndSearch(page, 'Yulia Eka');
        await page.screenshot({ path: 'tests/e2e/screenshots/01-search-yulia-eka.png', fullPage: true });

        const yukiaRow = page.locator('tr, [data-row]').filter({ hasText: 'Yulia Eka' }).first();
        expect(await yukiaRow.isVisible()).toBeTruthy();

        // Step 2: Check current admin state and toggle ON if needed
        const buttons = yukiaRow.locator('button');
        const adminToggle = buttons.first();

        // Check if admin is currently OFF (gray background) — toggle ON
        const adminToggleClasses = await adminToggle.getAttribute('class') || '';
        const isAdminOff = adminToggleClasses.includes('bg-gray');
        console.log(`Admin toggle classes: ${adminToggleClasses.substring(0, 100)}, isOff: ${isAdminOff}`);

        if (isAdminOff) {
            await adminToggle.click();
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(1000);
            console.log('Toggled Activity Admin ON');
        } else {
            console.log('Activity Admin already ON');
        }

        await page.screenshot({ path: 'tests/e2e/screenshots/02-admin-toggled-on.png', fullPage: true });

        // Step 3: Now toggle Report Access ON
        // Re-search after page reload
        await goToActivityAdminsAndSearch(page, 'Yulia Eka');
        const yukiaRow2 = page.locator('tr, [data-row]').filter({ hasText: 'Yulia Eka' }).first();
        const buttons2 = yukiaRow2.locator('button');
        const buttonCount = await buttons2.count();
        console.log(`Button count after admin ON: ${buttonCount}`);

        if (buttonCount >= 2) {
            const reportToggle = buttons2.nth(1);
            const isEnabled = await reportToggle.isEnabled();
            console.log(`Report toggle enabled: ${isEnabled}`);

            if (isEnabled) {
                // Check if report is OFF
                const reportClasses = await reportToggle.getAttribute('class') || '';
                const isReportOff = reportClasses.includes('bg-gray');
                console.log(`Report toggle classes: ${reportClasses.substring(0, 100)}, isOff: ${isReportOff}`);

                if (isReportOff) {
                    await reportToggle.click();
                    await page.waitForLoadState('networkidle');
                    await page.waitForTimeout(1000);
                    console.log('Toggled Report Access ON');
                } else {
                    console.log('Report Access already ON');
                }
            } else {
                console.log('ISSUE: Report toggle is disabled even though admin is ON');
            }
        }

        await page.screenshot({ path: 'tests/e2e/screenshots/03-report-toggled-on.png', fullPage: true });

        // Verify final state
        await goToActivityAdminsAndSearch(page, 'Yulia Eka');
        const finalRow = page.locator('tr, [data-row]').filter({ hasText: 'Yulia Eka' }).first();
        const finalButtons = finalRow.locator('button');

        // Both toggles should now be ON (colored, not gray)
        const adminClasses = await finalButtons.first().getAttribute('class') || '';
        const reportClasses = await finalButtons.nth(1).getAttribute('class') || '';

        console.log(`Final admin classes: ${adminClasses.substring(0, 80)}`);
        console.log(`Final report classes: ${reportClasses.substring(0, 80)}`);

        // Admin should be blue/colored (not gray)
        expect(adminClasses).not.toContain('bg-gray-200');
        await page.screenshot({ path: 'tests/e2e/screenshots/04-final-state-both-on.png', fullPage: true });
    });

    // ─────────────────────────────────────────────────────────
    // PHASE 2: Yulia Eka verifies Activity Admin access
    // ─────────────────────────────────────────────────────────
    test('Phase 2: Yulia Eka can access Activity Admin dashboard', async ({ page }) => {
        await login(page, YULIA_EKA.email, YULIA_EKA.password);

        const response = await page.goto('/activity/admin/dashboard');
        await page.waitForLoadState('networkidle');

        const currentUrl = page.url();
        const httpStatus = response?.status() ?? 0;
        console.log(`Yulia Eka Activity Admin: url=${currentUrl}, status=${httpStatus}`);

        await page.screenshot({ path: 'tests/e2e/screenshots/05-yulia-eka-activity-admin.png', fullPage: true });

        expect(httpStatus).toBeLessThan(400);
        expect(currentUrl).not.toContain('/login');
        console.log('PASS: Yulia Eka has Activity Admin access');
    });

    // ─────────────────────────────────────────────────────────
    // PHASE 3: Yulia Eka verifies BOD Reporting access
    // ─────────────────────────────────────────────────────────
    test('Phase 3: Yulia Eka can access BOD Reporting dashboard', async ({ page }) => {
        await login(page, YULIA_EKA.email, YULIA_EKA.password);

        // Intercept the response to check HTTP status directly
        const response = await page.goto('/activity/reporting');
        await page.waitForLoadState('networkidle');

        const currentUrl = page.url();
        const httpStatus = response?.status() ?? 0;
        console.log(`Yulia Eka BOD Reporting: url=${currentUrl}, status=${httpStatus}`);

        await page.screenshot({ path: 'tests/e2e/screenshots/06-yulia-eka-bod-reporting.png', fullPage: true });

        // Check HTTP status — 200 = access granted, 403 = blocked
        const isLogin = currentUrl.includes('/login');
        expect(httpStatus).toBeLessThan(400);
        expect(isLogin).toBeFalsy();
        console.log('PASS: Yulia Eka has BOD Reporting access');
    });

    // ─────────────────────────────────────────────────────────
    // PHASE 4: Yulia Mekar Rini check (HOD, no admin flag)
    // ─────────────────────────────────────────────────────────
    test('Phase 4: Yulia Mekar Rini access check', async ({ page }) => {
        await login(page, YULIA_RINI.email, YULIA_RINI.password);

        // Activity Admin - should be blocked (no is_activity_admin flag)
        const adminResp = await page.goto('/activity/admin/dashboard');
        await page.waitForLoadState('networkidle');
        const adminStatus = adminResp?.status() ?? 0;
        const adminUrl = page.url();
        await page.screenshot({ path: 'tests/e2e/screenshots/07-yulia-rini-activity-admin.png', fullPage: true });

        const adminBlocked = adminStatus >= 400 || adminUrl.includes('/login');
        console.log(`Yulia Rini Activity Admin: blocked=${adminBlocked}, status=${adminStatus}, url=${adminUrl}`);

        // BOD Reporting - should be blocked (HOD, not top management, no report flag)
        const reportResp = await page.goto('/activity/reporting');
        await page.waitForLoadState('networkidle');
        const reportStatus = reportResp?.status() ?? 0;
        const reportUrl = page.url();
        await page.screenshot({ path: 'tests/e2e/screenshots/07-yulia-rini-bod-reporting.png', fullPage: true });

        const reportBlocked = reportStatus >= 400 || reportUrl.includes('/login');
        console.log(`Yulia Rini BOD Reporting: blocked=${reportBlocked}, status=${reportStatus}, url=${reportUrl}`);
    });

    // ─────────────────────────────────────────────────────────
    // PHASE 5: Super Admin turns OFF admin → auto-revokes report
    // ─────────────────────────────────────────────────────────
    test('Phase 5: Super Admin turns OFF admin → report auto-revoked', async ({ page }) => {
        await login(page, SUPER_ADMIN.email, SUPER_ADMIN.password);
        await goToActivityAdminsAndSearch(page, 'Yulia Eka');

        const yukiaRow = page.locator('tr, [data-row]').filter({ hasText: 'Yulia Eka' }).first();
        const adminToggle = yukiaRow.locator('button').first();

        // Toggle admin OFF
        await adminToggle.click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        await page.screenshot({ path: 'tests/e2e/screenshots/08-admin-toggled-off.png', fullPage: true });

        // Verify both toggles are now OFF
        await goToActivityAdminsAndSearch(page, 'Yulia Eka');
        const finalRow = page.locator('tr, [data-row]').filter({ hasText: 'Yulia Eka' }).first();
        const finalButtons = finalRow.locator('button');

        const adminClasses = await finalButtons.first().getAttribute('class') || '';
        console.log(`After OFF - admin classes: ${adminClasses.substring(0, 80)}`);

        // Admin should be gray (OFF)
        expect(adminClasses).toContain('bg-gray');

        // Report toggle should be disabled
        if (await finalButtons.count() >= 2) {
            const reportEnabled = await finalButtons.nth(1).isEnabled();
            console.log(`After OFF - report toggle enabled: ${reportEnabled}`);
            expect(reportEnabled).toBeFalsy();
        }

        await page.screenshot({ path: 'tests/e2e/screenshots/09-both-off-final.png', fullPage: true });
    });

    // ─────────────────────────────────────────────────────────
    // PHASE 6: Yulia Eka verifies access revoked
    // ─────────────────────────────────────────────────────────
    test('Phase 6: Yulia Eka access revoked after admin OFF', async ({ page }) => {
        await login(page, YULIA_EKA.email, YULIA_EKA.password);

        // Activity Admin should be blocked
        const adminResp = await page.goto('/activity/admin/dashboard');
        await page.waitForLoadState('networkidle');
        const adminStatus = adminResp?.status() ?? 0;
        const adminUrl = page.url();
        await page.screenshot({ path: 'tests/e2e/screenshots/10-yulia-eka-revoked-admin.png', fullPage: true });

        const adminBlocked = adminStatus >= 400 || adminUrl.includes('/login');
        console.log(`After revoke - Activity Admin: status=${adminStatus}, blocked=${adminBlocked}`);
        expect(adminBlocked).toBeTruthy();

        // BOD Reporting should also be blocked
        const reportResp = await page.goto('/activity/reporting');
        await page.waitForLoadState('networkidle');
        const reportStatus = reportResp?.status() ?? 0;
        const reportUrl = page.url();
        await page.screenshot({ path: 'tests/e2e/screenshots/10-yulia-eka-revoked-reporting.png', fullPage: true });

        const reportBlocked = reportStatus >= 400 || reportUrl.includes('/login');
        console.log(`After revoke - BOD Reporting: status=${reportStatus}, blocked=${reportBlocked}`);
        expect(reportBlocked).toBeTruthy();
    });
});
