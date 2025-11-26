# Testing Guide - Email Notification System

## Prerequisites

Before testing, ensure:
- ✅ Migrations run successfully: `php artisan migrate`
- ✅ Caches cleared: `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- ✅ SMTP credentials available (Gmail App Password recommended)

---

## Phase 1: Admin Configuration Testing

### Step 1: Access Admin Panel
1. Login as **Super Admin**
2. Navigate to: `/admin/notification-settings`
3. **Expected Result**: Settings page loads with 4 statistics cards at top

### Step 2: Configure SMTP Settings
1. Fill in SMTP configuration:
   - **SMTP Host**: `smtp.gmail.com`
   - **SMTP Port**: `587`
   - **SMTP Username**: Your Gmail address
   - **SMTP Password**: [Your App Password](https://myaccount.google.com/apppasswords)
   - **Encryption**: `TLS (Recommended - Port 587)`

2. Fill in Email Settings:
   - **From Email**: `noreply@werkudara.com` (or your domain)
   - **From Name**: `WNS Purchase Request System`

3. Configure Notification Options:
   - ✅ Enable Email Notifications (checked)
   - ✅ Enable Database Fallback (checked)
   - **Link Expiry**: 3 days (slider)

4. Click **Save Settings**

**Expected Result**: 
- ✅ Green success message: "Settings saved successfully"
- Statistics update (if this is first save)

### Step 3: Test Email Sending
1. Scroll to "Send Test Email" section
2. Enter your email address
3. Click **Send Test**

**Expected Results**:
- ✅ Success message: "Test email sent successfully"
- ✅ Email received in inbox within 1-2 minutes
- ✅ Email has purple gradient header
- ✅ Email contains "This is a test email" message
- ✅ Email sender shows configured FROM name

**Troubleshooting**:
- ❌ If error "Failed to send test email":
  - Check SMTP credentials are correct
  - Verify Gmail "Less secure app access" is OFF (use App Password instead)
  - Check port 587 is not blocked by firewall
  - Try changing encryption to SSL (port 465)

---

## Phase 2: PR Submission Flow Testing

### Step 4: Create & Submit Purchase Request
1. Login as **regular user** (non-admin)
2. Navigate to: `/purchase-requests/create`
3. Fill in PR details:
   - Business Unit: Select any
   - Description: "Test email notification system"
   - Add 2-3 items with total > Rp 500,000 (to trigger approval)
4. Click **Save as Draft**
5. Click **Submit for Approval**

**Expected Results**:
- ✅ PR status changes to `submitted` → `in_approval`
- ✅ Success message: "Purchase request submitted successfully"
- ✅ Redirect to PR show page

### Step 5: Verify Email Sent to First Approver
1. Check **Super Admin email statistics**:
   - Navigate to: `/admin/notification-settings`
   - **Total Sent** should increment by 1
   - **Last Sent** should show "a few seconds ago"

2. Check **Approver's email inbox**:
   - ✅ Email received with subject: "Action Required: Purchase Request Approval"
   - ✅ Email contains PR number, requestor name, amount
   - ✅ Email has blue "Review & Approve/Reject" button
   - ✅ Yellow warning box: "This link expires in 3 days"

**Check Database Notification**:
```sql
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 1;
```
- ✅ Record exists with `notifiable_id` = approver's user ID
- ✅ `data` JSON contains `pr_id`, `pr_number`, `approval_id`, `action_url`

---

## Phase 3: Public Approval Link Testing

### Step 6: Test Public Approval Page
1. Open email received by approver
2. Click **"Review & Approve/Reject"** button
3. **Expected Result**: Opens in browser without login prompt

**Page Validation**:
- ✅ Page loads without authentication
- ✅ Shows PR details (number, requestor, amount, items)
- ✅ Shows approver role (Department Head, Finance Manager, etc.)
- ✅ Shows 2 decision options: Approve / Reject (radio buttons)
- ✅ Notes textarea visible
- ✅ Footer shows "Login to view full details" link

### Step 7: Test Approval Submission
1. Select **"Approve"** radio button
2. Enter notes (optional): "Approved for testing"
3. Click **Submit Decision**

**Expected Results**:
- ✅ Success page loads with green checkmark icon
- ✅ Shows "Request Approved! 🎉" heading
- ✅ Shows decision details (PR number, decision, timestamp, amount)
- ✅ Shows notes entered
- ✅ Blue info box: "What happens next?"
- ✅ Confetti animation plays (if approved)

**Check Database**:
```sql
SELECT status, responded_at, approval_notes 
FROM pr_approvals 
WHERE id = [approval_id];
```
- ✅ `status` = 'approved'
- ✅ `responded_at` = current timestamp
- ✅ `approval_notes` = "Approved for testing"

### Step 8: Test Rejection Flow
1. Submit another PR (repeat Step 4)
2. Open approver email
3. Click approval link
4. Select **"Reject"** radio button
5. **DO NOT** enter notes yet
6. Click **Submit Decision**

**Expected Result**:
- ❌ Error message: "Notes are required when rejecting a request"
- ✅ Form stays on page with input preserved

7. Enter notes: "Missing budget approval"
8. Click **Submit Decision** again

**Expected Results**:
- ✅ Success page with red X icon
- ✅ Shows "Request Rejected" heading
- ✅ Shows rejection notes in yellow box
- ✅ Yellow info box: "requestor can edit and resubmit"

**Check Email**:
- ✅ Requestor receives rejection email
- ✅ Email subject: "Purchase Request Rejected"
- ✅ Red rejection box with notes: "Missing budget approval"
- ✅ Shows "Edit & Resubmit" button

---

## Phase 4: Sequential Approval Testing

### Step 9: Test Multi-Step Approval
1. Create PR with amount **> Rp 5,000,000** (requires 3+ approvers)
2. Submit for approval
3. **First Approver** (Department Head):
   - ✅ Receives email immediately
   - Approves via public link
   - ✅ Success page confirms approval

4. **Second Approver** (Finance Manager):
   - ✅ Receives email after first approval
   - ✅ Email mentions "Step 2 of X"
   - Approves via public link

5. **Third Approver** (General Manager):
   - ✅ Receives email after second approval
   - Approves via public link

6. **Requestor**:
   - ✅ Receives "Purchase Request Fully Approved" email
   - ✅ Email shows approval chain with all approvers
   - ✅ Email has "Download PDF" button

**Validation**:
- ✅ Each approver only receives email when it's their turn
- ✅ Sequential flow maintained (no parallel approvals)
- ✅ Final completion email includes all approvers with dates

---

## Phase 5: Error Scenario Testing

### Step 10: Test Expired Link
1. Find old approval email (or manually create signed URL)
2. Modify URL to use past timestamp
3. Click link

**Expected Result**:
- ✅ Error page: "Approval Already Processed" or "Link Expired"
- ✅ Shows explanation message
- ✅ Shows "Go to Homepage" and "Go Back" buttons

### Step 11: Test Double Approval Prevention
1. Approve a PR via public link
2. Copy the URL from browser
3. Try to access the same URL again

**Expected Result**:
- ✅ Error page: "Approval Already Processed"
- ✅ Shows current status (approved)
- ✅ Prevents duplicate approval action

### Step 12: Test SMTP Failure Fallback
1. Go to `/admin/notification-settings`
2. Change SMTP password to **incorrect value**
3. Click **Save Settings**
4. Submit a new PR

**Expected Results**:
- ✅ PR submission succeeds (doesn't fail)
- ✅ Statistics show "Total Failed" increments
- ✅ Database notification still created (check `notifications` table)
- ✅ Laravel log shows error: `storage/logs/laravel.log`

**Check Database**:
```sql
SELECT * FROM notifications 
WHERE notifiable_id = [approver_id] 
ORDER BY created_at DESC LIMIT 1;
```
- ✅ Record exists even though email failed
- ✅ `read_at` is NULL (unread)

5. **Fix SMTP password** and test email works again

---

## Phase 6: Link Expiry Configuration Testing

### Step 13: Test Custom Link Expiry
1. Go to `/admin/notification-settings`
2. Move **Link Expiry** slider to **7 days**
3. Click **Save Settings**
4. Submit a new PR

**Check Email**:
- ✅ Warning box shows "This link expires in 7 days"

**Check Signed URL**:
- View email HTML source
- Find the approval link URL
- ✅ URL contains `expires=` parameter with timestamp 7 days from now

---

## Phase 7: Performance Testing

### Step 14: Test Email Sending Speed
1. Enable query logging: Add to `AppServiceProvider::boot()`:
   ```php
   \DB::listen(function($query) {
       \Log::info($query->sql, $query->bindings);
   });
   ```
2. Submit PR
3. Check `storage/logs/laravel.log`

**Expected**:
- ✅ Email sends within 5 seconds
- ✅ No N+1 query issues (should see ~5-8 queries total)
- ✅ Settings cached (only 1 query to `notification_settings` table per request)

### Step 15: Test Concurrent Submissions
1. Open 3 browser tabs
2. Create PR in each tab simultaneously
3. Submit all 3 at same time

**Expected Results**:
- ✅ All 3 PRs submitted successfully
- ✅ All 3 approval emails sent
- ✅ Statistics show "Total Sent" = +3
- ✅ No database deadlocks or errors

---

## Phase 8: UI/UX Validation

### Step 16: Mobile Responsive Testing
1. Open public approval link on mobile device (or Chrome DevTools mobile view)
2. Check email on mobile Gmail/Outlook app

**Expected Results**:
- ✅ Public approval page fully responsive
- ✅ Radio buttons easy to tap (large target area)
- ✅ Submit button accessible
- ✅ Email renders correctly in mobile clients
- ✅ No horizontal scrolling

### Step 17: Email Client Compatibility
Test email rendering in:
- ✅ Gmail (web + mobile)
- ✅ Outlook (web + desktop)
- ✅ Apple Mail
- ✅ ProtonMail (if using encryption)

**Validation**:
- ✅ Gradient header displays correctly
- ✅ Buttons render (not showing as broken images)
- ✅ Links are clickable
- ✅ Text is readable (good contrast)

---

## Troubleshooting Guide

### Issue: "Class NotificationSetting not found"
**Solution**: Run `composer dump-autoload`

### Issue: "Route not found"
**Solution**: 
```bash
php artisan route:clear
php artisan route:cache
```

### Issue: "View not found"
**Solution**:
```bash
php artisan view:clear
php artisan view:cache
```

### Issue: "Too many redirects" on signed URL
**Solution**: Check `.env` has correct `APP_URL` (no trailing slash)

### Issue: Gmail blocks email
**Solution**:
1. Use App Password (not regular password)
2. Enable 2FA on Gmail account first
3. Generate App Password: https://myaccount.google.com/apppasswords
4. Use 16-character password in settings (no spaces)

### Issue: "Signature verification failed"
**Solution**: 
1. Check `APP_KEY` is set in `.env`
2. Don't change `APP_KEY` after generating signed URLs
3. Check server time is synchronized (NTP)

---

## Success Criteria

All tests pass if:
- ✅ Admin can configure SMTP settings
- ✅ Test email sends successfully
- ✅ Approver receives email on PR submission
- ✅ Public approval link works without login
- ✅ Approval/rejection updates database
- ✅ Sequential approval workflow maintained
- ✅ Emails trigger on each approval step
- ✅ Completion email sent to requestor
- ✅ Database fallback works if SMTP fails
- ✅ Error pages show for invalid/expired links
- ✅ Statistics track sent/failed emails accurately

---

## Performance Benchmarks

Target metrics:
- Email send time: < 5 seconds
- Page load (public approval): < 1 second
- Settings cache hit rate: > 95%
- Email delivery rate: > 98%
- Zero N+1 queries in approval workflow

---

## Next Steps After Testing

1. **Monitor production logs** for first week
2. **Collect user feedback** on email clarity
3. **Adjust link expiry** based on actual usage patterns
4. **Add retry mechanism** for failed emails (future feature)
5. **Implement notification bell** component (Phase 2)
6. **Create notification center** UI (Phase 2)

---

**Testing Date**: _________________
**Tested By**: _________________
**Test Environment**: Development / Staging / Production
**All Tests Passed**: ✅ / ❌
