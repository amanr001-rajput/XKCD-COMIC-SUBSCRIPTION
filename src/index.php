<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');
ini_set('log_errors', 1);

// Include helper functions (email, verification, registration, etc.)
include 'functions.php';

// Initialize state variables for UI and logic
$showVerification = false; // Whether to show the verification code form
$emailValue = '';
$message = '';
$isSubscribed = false; // Whether the user has successfully subscribed

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: User submits email for subscription (no verification code yet)
    if (isset($_POST['email']) && !isset($_POST['verification_code'])) {
        $email = trim($_POST['email']);
        $code = generateVerificationCode(); // Generate a 6-digit code
        sendVerificationEmail($email, $code); // Email the code to user
        $showVerification = true; // Show verification form
        $emailValue = htmlspecialchars($email); // Pre-fill email in form
        $message = "<div class='success'>Verification code sent to <strong>$email</strong></div>";
    }

    // Step 2: User submits email and verification code
    if (isset($_POST['email'], $_POST['verification_code'])) {
        $email = trim($_POST['email']);
        $code = trim($_POST['verification_code']);
        if (verifyCode($email, $code)) { // Check if code is valid
            if (registerEmail($email)) { // Register email if verified
                $isSubscribed = true;
                $message = "<div class='success'>Successfully subscribed to XKCD Comics! Your first comic will arrive soon.</div>";
                $showVerification = false;
                $emailValue = '';
            } else {
                $message = "<div class='error'>Failed to register email. Please try again.</div>";
                $showVerification = false;
                $emailValue = htmlspecialchars($email);
            }
        } else {
            $message = "<div class='error'>Invalid verification code. Please try again.</div>";
            $showVerification = true;
            $emailValue = htmlspecialchars($email);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>XKCD Comic Subscription</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&display=swap');
        
        :root {
            --primary-color: #2196f3;
            --secondary-color: #ff4081;
            --success-color: #4caf50;
            --error-color: #f44336;
            --background: #f5f5f5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Comic Neue', cursive;
            background: var(--background);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            color: #333;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            text-align: center;
            color: #333;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: var(--primary-color);
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover {
            background: #1976d2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        button:active {
            transform: translateY(0);
        }

        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .error {
            background: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }

        .debug {
            background: #fff3e0;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-family: monospace;
        }

        .comic-decoration {
            position: fixed;
            bottom: 20px;
            right: 20px;
            font-size: 2rem;
            color: #ccc;
            transform: rotate(-10deg);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            animation: fadeIn 0.5s ease forwards;
        }

        .verification-section {
            animation: fadeIn 0.5s ease forwards;
        }

        .welcome-message {
            text-align: center;
            margin-bottom: 2rem;
            color: #666;
            line-height: 1.6;
        }
        
        .subscription-status {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .subscription-status h2 {
            color: #2e7d32;
            margin-bottom: 1rem;
        }
        
        .subscription-status p {
            color: #1b5e20;
        }
        
        .new-subscription {
            margin-top: 2rem;
            text-align: center;
        }
        
        .new-subscription button {
            background: var(--secondary-color);
        }
        
        .nav-links {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .nav-links a {
            color: #666;
            text-decoration: none;
            margin: 0 15px;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background: #f5f5f5;
            color: #333;
        }
        
        .nav-links a.unsubscribe {
            color: var(--error-color);
        }
        
        .nav-links a.unsubscribe:hover {
            background: #ffebee;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="/">Subscribe</a>
            <a href="/unsubscribe.php" class="unsubscribe">Unsubscribe</a>
        </div>

        <?php if ($isSubscribed): ?>
            <!-- Show success message and option to subscribe another email -->
            <div class="subscription-status">
                <h2>🎉 Successfully Subscribed!</h2>
                <p>We've just sent your first XKCD comic to your email.</p>
                <p>You'll continue to receive a new comic every day!</p>
            </div>
            <div class="new-subscription">
                <h3>Want to subscribe another email?</h3>
                <form method="POST">
                    <div class="form-group">
                        <input type="email" name="email" required placeholder="Enter another email address">
                        <button id="submit-email" type="submit">Subscribe New Email</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <h1>XKCD Comic Subscription</h1>
            
            <?php if (!$showVerification): ?>
            <!-- Welcome message for new users -->
            <div class="welcome-message">
                <p>Subscribe to receive a random XKCD comic in your inbox every day!</p>
                <p>You'll get your first comic immediately after subscription.</p>
            </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <!-- Show success or error messages -->
                <?php echo $message; ?>
            <?php endif; ?>

            <?php if (!$showVerification): ?>
                <!-- Step 1: Email input form -->
                <form method="POST">
                    <div class="form-group">
                        <input type="email" name="email" required 
                               value="<?php echo $emailValue; ?>"
                               placeholder="Enter your email address">
                        <button id="submit-email" type="submit">Subscribe</button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Step 2: Verification code input form -->
                <form method="POST">
                    <div class="form-group">
                        <input type="hidden" name="email" value="<?php echo $emailValue; ?>">
                        <input type="text" name="verification_code" maxlength="6" required 
                               placeholder="Enter 6-digit verification code"
                               pattern="[0-9]{6}">
                        <button id="submit-verification" type="submit">Verify Email</button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="footer">
            <p>Want to stop receiving comics? <a href="/unsubscribe.php">Unsubscribe here</a></p>
        </div>
    </div>
    
    <div class="comic-decoration">✎</div>
</body>
</html>