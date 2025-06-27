<?php
/**
 * XKCD Comic Email Subscription System
 * 
 * This file contains the core functions for the XKCD comic subscription service.
 * The system allows users to:
 * 1. Register their email (with verification)
 * 2. Receive daily XKCD comics
 * 3. Unsubscribe from the service
 */

require_once __DIR__ . '/SmtpMailer.php';

/**
 * Generates a 6-digit verification code for email verification
 * 
 * @return string A 6-digit code padded with zeros if necessary
 */
function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Registers a new email for the XKCD comic subscription service
 * 
 * This function:
 * 1. Creates the registration file if it doesn't exist
 * 2. Checks for duplicate emails (case-insensitive)
 * 3. Safely appends new emails using file locking
 * 4. Maintains proper file permissions
 * 
 * @param string $email The email address to register
 * @return bool True if registration successful, False if duplicate or error
 */
function registerEmail($email) {
    $file = __DIR__ . '/registered_emails.txt';
    $email = trim($email);

    // Step 1: Create or ensure the registration file exists
    if (!file_exists($file)) {
        file_put_contents($file, '', LOCK_EX);
        chmod($file, 0666);  // Set permissions for read/write access
    }

    // Step 2: Read and parse existing emails
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $emails = array_map('trim', $emails);

    // Step 3: Check for duplicate emails (case-insensitive)
    foreach ($emails as $existing) {
        if (strcasecmp($existing, $email) === 0) {
            error_log("Email already registered: $email");
            return false;
        }
    }

    // Step 4: Append new email with proper formatting
    $result = file_put_contents(
        $file, 
        ($emails ? implode("\n", $emails) . "\n" : '') . $email . "\n",
        LOCK_EX  // Use exclusive file locking for thread safety
    );

    if ($result === false) {
        error_log("Failed to write email to file");
        return false;
    }

    error_log("Successfully registered new email: $email");
    return true;
    
    // Only add if email doesn't exist
    if (!in_array($email, $emails)) {
        error_log("Email doesn't exist, adding it");
        // Add new email
        $emails[] = $email;
        // Write back to file
        $content = implode(PHP_EOL, $emails) . PHP_EOL;
        $result = file_put_contents($file, $content, LOCK_EX);
        error_log("Writing to file: $content");
        error_log("File write result: " . ($result !== false ? "success ($result bytes)" : "failed"));
        
        // Send welcome comic
        $subject = 'Your XKCD Comic';
        $body = fetchAndFormatXKCDData();
        $unsubscribeLink = "http://localhost:8000/unsubscribe.php?email=" . urlencode($email);
        $body .= "<p><a href='{$unsubscribeLink}' id='unsubscribe-button'>Unsubscribe</a></p>";
        $mailer = new SmtpMailer();
        $mailer->send($email, $subject, $body);
        return true;
    }
    return false;
}

/**
 * Removes an email from the subscription list
 * 
 * This function:
 * 1. Reads the current subscription list
 * 2. Removes the specified email (case-insensitive)
 * 3. Rewrites the file with remaining emails
 * 
 * @param string $email The email address to unsubscribe
 * @return bool True if unsubscribed successfully, False if error
 */
function unsubscribeEmail($email) {
    $file = __DIR__ . '/registered_emails.txt';
    $email = trim($email);
    
    if (!file_exists($file)) {
        return false;
    }
    
    // Read and clean email list
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $emails = array_map('trim', $emails);
    
    // Remove target email (case-insensitive)
    $emails = array_filter($emails, function($e) use ($email) {
        return strcasecmp($e, $email) !== 0;
    });
    
    // Write remaining emails back to file
    file_put_contents($file, implode(PHP_EOL, array_values($emails)) . (empty($emails) ? '' : PHP_EOL));
    return true;
}

/**
 * Sends a verification email with a code
 * 
 * Used for both subscription and unsubscription verification.
 * Stores the verification code in a temporary file.
 * 
 * @param string $email The recipient's email address
 * @param string $code The verification code to send
 * @param bool $unsubscribe Whether this is for unsubscription (default: false)
 * @return bool True if email sent successfully
 */
function sendVerificationEmail($email, $code, $unsubscribe = false) {
    $email = trim($email);
    $subject = $unsubscribe ? 'Confirm Un-subscription' : 'Your Verification Code';
    $body = $unsubscribe
        ? "<p>To confirm un-subscription, use this code: <strong>$code</strong></p>"
        : "<p>Your verification code is: <strong>$code</strong></p>";
    
    // Store verification code securely
    $codeFile = __DIR__ . "/$email.code";
    file_put_contents($codeFile, $code);
    chmod($codeFile, 0666);  // Allow read/write for verification
    
    // Send verification email
    $mailer = new SmtpMailer();
    return $mailer->send($email, $subject, $body);
}

/**
 * Verifies a code against the stored code for an email
 * 
 * @param string $email The email address to verify
 * @param string $code The code to verify
 * @return bool True if code matches and is valid
 */
function verifyCode($email, $code) {
    $email = trim($email);
    $code = trim($code);
    $file = __DIR__ . "/$email.code";
    
    if (!file_exists($file)) {
        error_log("Code file not found for email: $email");
        return false;
    }
    
    $savedCode = trim(file_get_contents($file));
    if ($savedCode === $code) {
        unlink($file);  // Clean up after successful verification
        return true;
    }
    return false;
}

/**
 * Fetches and formats a random XKCD comic
 * 
 * Retrieves a random comic from XKCD's API and formats it
 * for email distribution. Falls back to comic #1 if fetch fails.
 * 
 * @return string Formatted HTML of the comic
 */
function fetchAndFormatXKCDData() {
    // Get a random comic (XKCD has ~2800 comics)
    $randId = rand(1, 2800);
    $url = "https://xkcd.com/$randId/info.0.json";
    $json = @file_get_contents($url);
    if ($json === false) {
        // Fallback to first comic if random fetch fails
        $json = file_get_contents("https://xkcd.com/1/info.0.json");
    }
    $data = json_decode($json, true);
    return "<h2>XKCD Comic</h2>" .
           "<img src='{$data['img']}' alt='XKCD Comic'>";
}

function sendXKCDUpdatesToSubscribers() {
    $file = __DIR__ . '/registered_emails.txt';
    if (!file_exists($file)) {
        error_log("Subscribers file not found: $file");
        return;
    }
    
    $content = file_get_contents($file);
    if (empty($content)) {
        error_log("No subscribers found in file");
        return;
    }
    
    $emails = array_filter(
        array_map('trim', file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)),
        'strlen'
    );
    
    if (empty($emails)) {
        error_log("No valid emails found in subscribers file");
        return;
    }
    
    $body = fetchAndFormatXKCDData();
    $mailer = new SmtpMailer();
    
    foreach ($emails as $email) {
        if (empty($email)) continue;
        $unsubscribeLink = "http://localhost:8000/unsubscribe.php?email=" . urlencode($email);
        $fullBody = $body . "<p><a href='{$unsubscribeLink}' id='unsubscribe-button'>Unsubscribe</a></p>";
        if (!$mailer->send($email, 'Your XKCD Comic', $fullBody)) {
            error_log("Failed to send comic to: $email");
        }
    }
}