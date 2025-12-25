# Email Notification Setup Instructions

## Current Status
Email notifications have been implemented but require configuration to work.

## Issues Found
1. **PHPMailer is not installed** - The package is listed in composer.json but needs to be installed
2. **Email password is not configured** - The SMTP password in `includes/email-config.php` is empty

## Steps to Enable Email Notifications

### Step 1: Install PHPMailer
Run the following command in your project root directory:
```bash
composer install
```
Or if composer is not in your PATH:
```bash
php composer.phar install
```

### Step 2: Configure Email Settings
Edit `includes/email-config.php` and set your SMTP credentials:

```php
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'barangay.martines@gmail.com',  // Your email
    'smtp_password' => 'YOUR_APP_PASSWORD_HERE',  // ⚠️ SET THIS!
    'smtp_encryption' => 'tls',
    'from_email' => 'barangay.martines@gmail.com',
    'from_name' => 'Barangay Population Management System'
];
```

### Step 3: Gmail Setup (if using Gmail)
1. Go to your Google Account settings
2. Enable 2-Step Verification
3. Generate an App Password:
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Enter "Barangay System"
   - Copy the generated 16-character password
   - Paste it in `email-config.php` as `smtp_password`

### Step 4: Test Email Sending
1. Make sure a user has:
   - An email address in their profile
   - Email notifications enabled (checked the box during signup)
2. Update a request/complaint/appointment status
3. Check PHP error logs for email sending status:
   - Look for messages like "EmailHelper: Email sent successfully" or error messages

## Debugging

### Check PHP Error Logs
The system logs detailed information about email sending attempts. Check your PHP error log for:
- "Email notification check" - Shows if email notifications are enabled
- "Attempting to send email" - Shows when email sending is attempted
- "Email send result" - Shows success/failure
- "EmailHelper:" messages - Shows PHPMailer initialization and sending status

### Common Issues

1. **"PHPMailer is not installed"**
   - Solution: Run `composer install`

2. **"SMTP password is not configured"**
   - Solution: Set the password in `includes/email-config.php`

3. **"Email notification skipped - Not enabled or no email address"**
   - Solution: Make sure the user has:
     - Email notifications enabled (email_notifications = 1 in database)
     - A valid email address

4. **SMTP Authentication Failed**
   - Solution: Check your email and password are correct
   - For Gmail: Use an App Password, not your regular password

## Files Modified
- `includes/email-helper.php` - Email sending functionality
- `includes/email-config.php` - Email configuration
- All status update handlers now include email sending with logging

## Database Migration
If you have existing users, run:
```bash
php database/add_email_notifications_column.php
```

This adds the `email_notifications` column to the `user` table.
