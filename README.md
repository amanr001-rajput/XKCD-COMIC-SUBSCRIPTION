# XKCD Comic Email Subscription Service

A PHP-based web application that sends daily XKCD comics to subscribed users via email.

## 1. Project Overview
This is a PHP web application that lets users subscribe to receive daily XKCD comics by email. It uses only flat files (no database), and all emails are sent in HTML format with an unsubscribe button.

## 2. Quick Start Guide
### Initial Setup
- **Set file permissions:**
  ```bash
  chmod 666 registered_emails.txt
  chmod +x setup_cron.sh
  ```
- **Start the PHP server:**
  ```bash
  php -S localhost:8000
  ```
- **Start Mailpit:**
  In a new terminal, run `mailpit` to catch and view outgoing emails.

### Access Points
- Website: http://localhost:8000
- Email Interface: http://localhost:8025

### Test Flow
1. Subscribe with your email on the website.
2. Check Mailpit for the verification code.
3. Enter the code to complete your subscription.
4. You’ll receive a welcome comic.
5. You’ll get daily comics at 9:00 AM (via cron).

## 3. Project Structure
### Core Files
- **index.php**: The main web page for subscribing and verifying emails.
- **functions.php**: Contains all core logic: email registration, verification, comic fetching, file operations, etc.
- **cron.php**: Script run by cron to send the daily comic to all registered users.
- **SmtpMailer.php**: Custom class for sending HTML emails via SMTP (Mailpit-compatible).
- **unsubscribe.php**: Handles the unsubscribe process, including verification.

### Config Files
- **mail.ini**: Email server settings (host, port, sender address).
- **php.ini**: PHP configuration (if needed for custom settings).
- **setup_cron.sh**: Script to install the cron job for daily comic delivery.
- **registered_emails.txt**: Stores the list of subscribed email addresses (one per line).

## 4. Features
### Email Registration
- **Double opt-in:** Users must verify their email with a code before being added.
- **Duplicate prevention:** The same email cannot be registered twice (case-insensitive).
- **Welcome comic:** A comic is sent immediately after successful registration.
- **Secure file storage:** All emails are stored in a flat file with safe file operations.

### Daily Comics
- **Automated delivery:** Comics are sent every day at 9:00 AM via cron.
- **Random XKCD selection:** Each day’s comic is randomly chosen.
- **HTML-formatted emails:** Comics are sent as HTML emails (with images and formatting).
- **Unsubscribe button:** Every email includes a one-click unsubscribe link.

### Security
- **No hardcoded verification codes:** Codes are generated randomly for each request.
- **Safe file operations:** Uses file locking and proper permissions.
- **Concurrent access handling:** Prevents race conditions when reading/writing the email list.
- **Email verification for all actions:** Both subscribe and unsubscribe require verification.

## 5. Testing Steps
### Registration
- Start the PHP server and Mailpit.
- Visit the website, enter your email, and check Mailpit for the verification code.
- Enter the code to complete registration and receive a welcome comic.

### Comic Delivery
- Run `php cron.php` manually to test daily delivery.
- Check Mailpit for the comic email.
- Verify that the email is HTML formatted and includes the unsubscribe button.

### Unsubscribe
- Click the unsubscribe link in any comic email.
- Complete the verification process.
- Confirm that your email is removed from `registered_emails.txt`.

## 6. Troubleshooting
### Common Issues
- **Emails not sending:** Make sure Mailpit is running, check `mail.ini`, and verify file permissions.
- **File access errors:** Fix with:
  ```bash
  chmod 666 registered_emails.txt
  chmod +x setup_cron.sh
  ```
- **Cron not running:** Check `cron.log`, verify that `setup_cron.sh` was run, and check file paths and permissions.

## 7. Maintenance
- **Monitor `cron.log`** for delivery issues.
- **Check `registered_emails.txt`** for accuracy.
- **Clear old verification files** (e.g., `*.code` files).
- **Review error logs** for PHP or cron errors.

## 8. Requirements Met
- ✅ Email verification system (double opt-in)
- ✅ No hardcoded verification codes
- ✅ File-based storage (no database)
- ✅ HTML-formatted emails
- ✅ Working cron implementation
- ✅ Unsubscribe functionality
- ✅ All files in `src/` directory

## 9. Author & Support
- Add your name and contact info as needed.

**Summary:**
This project is a secure, file-based PHP system for XKCD comic email subscriptions. It uses only flat files, no database, and all emails are HTML with an unsubscribe button. The code is organized, commented, and meets all assignment requirements. The README provides all steps for setup, testing, and troubleshooting.
