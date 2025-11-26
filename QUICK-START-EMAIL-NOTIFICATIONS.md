# Quick Start - Email Notification System

## 🚀 5-Minute Setup Guide

### Step 1: Run Migrations
```bash
php artisan migrate
```
**Expected**: 2 new tables created (`notification_settings`, `notifications`)

### Step 2: Clear Caches
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 3: Configure SMTP (Super Admin Only)
1. Login as Super Admin
2. Navigate to: `/admin/notification-settings`
3. Fill in SMTP settings:
   - **Gmail Example**:
     - Host: `smtp.gmail.com`
     - Port: `587`
     - Encryption: `TLS`
     - Username: Your Gmail
     - Password: [App Password](https://myaccount.google.com/apppasswords) (16 chars, no spaces)
   - **Office 365 Example**:
     - Host: `smtp.office365.com`
     - Port: `587`
     - Encryption: `TLS`
     - Username: Your Office 365 email
     - Password: Your password
4. Set FROM address: `noreply@werkudara.com`
5. Set FROM name: `WNS Purchase Request System`
6. Check ✅ Enable Email Notifications
7. Check ✅ Enable Database Fallback
8. Set Link Expiry: `3 days`
9. Click **Save Settings**

### Step 4: Test Email
1. Enter your email in "Test Email Address" field
2. Click **Send Test**
3. Check inbox (should arrive in 1-2 minutes)

### Step 5: Test with Real PR
1. Login as regular user
2. Create new Purchase Request
3. Submit for approval
4. Check approver's email
5. Click approval link in email
6. Approve or reject

---

## 🎯 How It Works

### For Approvers:
1. **Email arrives** when PR needs your approval
2. **Click blue button** in email (no login required)
3. **Choose Approve/Reject** on secure page
4. **Submit decision** - done in 30 seconds!

### For Requestors:
1. **Submit PR** as usual
2. **Receive email** when approved/rejected
3. **Track progress** via email notifications
4. **Get completion email** with full approval chain

---

## 🔐 Security Features

- ✅ **Signed URLs** - Tamper-proof links with 3-day expiry
- ✅ **One-time use** - Can't approve twice with same link
- ✅ **Sequential validation** - Only current approver can act
- ✅ **Encrypted passwords** - SMTP credentials encrypted in database
- ✅ **Rate limiting** - 5 approval attempts per minute

---

## 📧 Email Types

| Email | Sent To | When | Action Required |
|-------|---------|------|-----------------|
| **Approval Requested** | Approver | PR submitted / Previous approver approved | ✅ Click link to approve/reject |
| **Approval Approved** | Requestor | Your approval step completed | ℹ️ Informational |
| **Approval Rejected** | Requestor | Any approver rejects | ⚠️ Edit & resubmit |
| **Approval Completed** | Requestor | All approvals done | 🎉 Success! |

---

## 🛠️ Admin Tools

Access: `/admin/notification-settings`

### Statistics Dashboard
- **Total Sent**: All emails sent since setup
- **Total Failed**: Failed email attempts
- **Success Rate**: Percentage of successful sends
- **Last Sent**: When last email was sent

### Configuration Options
- SMTP server settings
- Email sender info (FROM address/name)
- Enable/disable email notifications
- Database fallback toggle
- Link expiry duration (1-14 days)

### Test Email
- Send test to any email address
- Verify SMTP configuration works
- Check email rendering

---

## ⚙️ Configuration Files

### `.env` (Optional - Use Admin Panel Instead)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@werkudara.com
MAIL_FROM_NAME="WNS Purchase Request System"

NOTIFICATION_LINK_EXPIRY_DAYS=3
NOTIFICATION_EMAIL_TIMEOUT=5
NOTIFICATION_CACHE_TTL=3600
```

### `config/notification.php`
- Default notification settings
- Template configurations
- Cache TTL settings
- Retry behavior (future)

---

## 🐛 Troubleshooting

### Email Not Sending
1. Check SMTP credentials in admin panel
2. Click "Send Test" to verify configuration
3. Check `storage/logs/laravel.log` for errors
4. Verify port 587 not blocked by firewall

### Gmail Specific
- ❌ Don't use regular password
- ✅ Use App Password (requires 2FA)
- Generate: https://myaccount.google.com/apppasswords
- Use 16-character password (no spaces)

### Link Expired Error
- Links expire after configured days (default 3)
- Approver can login to system to approve manually
- Admin can adjust expiry in settings (1-14 days)

### Already Processed Error
- Link can only be used once
- Check PR status on main site
- Contact requestor if issue persists

---

## 📱 Mobile Support

All email templates and approval pages are mobile-responsive:
- ✅ Works on Gmail mobile app
- ✅ Works on Outlook mobile app
- ✅ Approval page optimized for mobile browsers
- ✅ Large tap targets for buttons
- ✅ No horizontal scrolling

---

## 🔄 Workflow Example

**Scenario**: PR for Rp 6,000,000 (requires Dept Head + Finance Manager + General Manager)

1. **09:00** - User submits PR
   - ✉️ Department Head receives email

2. **09:15** - Dept Head clicks email link, approves
   - ✉️ Finance Manager receives email
   - ✉️ Requestor receives "approved by Dept Head" email

3. **14:30** - Finance Manager approves via email
   - ✉️ General Manager receives email
   - ✉️ Requestor receives "approved by Finance Manager" email

4. **15:00** - General Manager approves
   - ✉️ Requestor receives "Fully Approved" email with approval chain
   - ✅ PR status = `approved`

**Total time**: 6 hours (vs days of manual follow-up)

---

## 📊 Success Metrics

Track in admin panel (`/admin/notification-settings/statistics`):
- Email delivery rate (target: >98%)
- Average approval time (before: days, after: hours)
- Failed email count (investigate if >2%)
- Peak sending times (optimize SMTP if needed)

---

## 🎓 User Training

### For Approvers (5 minutes):
1. "You'll receive emails when PRs need approval"
2. "Click the blue button - no login needed"
3. "Choose approve/reject, add notes, submit"
4. "That's it! System handles the rest"

### For Requestors (2 minutes):
1. "Submit PR as usual"
2. "You'll get email updates on progress"
3. "If rejected, email explains why"
4. "Edit and resubmit when ready"

---

## 🆘 Support

### For Users
- Login to system to view all approvals
- Check email spam folder if not receiving
- Contact admin if link expired

### For Admins
- Check `/admin/notification-settings/statistics`
- Review `storage/logs/laravel.log` for errors
- Test SMTP with "Send Test Email" feature
- Verify database fallback working (check `notifications` table)

---

## 📖 Additional Documentation

- **Full Implementation Guide**: `EMAIL-NOTIFICATION-IMPLEMENTATION.md`
- **Detailed Testing Guide**: `TESTING-GUIDE-EMAIL-NOTIFICATIONS.md`
- **Developer Documentation**: Comments in code files

---

**System Version**: v2.5-beta  
**Last Updated**: November 26, 2025  
**Status**: ✅ Production Ready (Phase 1 Complete)
