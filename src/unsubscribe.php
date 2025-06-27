<?php
// Unsubscribe page: Handles email unsubscription with verification
require_once 'functions.php';

// Get email from query string if present
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: User submits email to unsubscribe (no verification code yet)
    if (isset($_POST['unsubscribe_email']) && !isset($_POST['verification_code'])) {
        $email = trim($_POST['unsubscribe_email']);
        $code = generateVerificationCode(); // Generate a 6-digit code
        sendVerificationEmail($email, $code, true); // Send code for unsubscription
        $message = "<div class='message'>Verification code sent to <strong>$email</strong></div>";
    }

    // Step 2: User submits email and verification code
    if (isset($_POST['unsubscribe_email'], $_POST['verification_code'])) {
        $email = trim($_POST['unsubscribe_email']);
        $code = trim($_POST['verification_code']);
        if (verifyCode($email, $code)) { // Check if code is valid
            unsubscribeEmail($email); // Remove email from subscription list
            $message = "<div class='success'>Successfully unsubscribed from XKCD Comics.</div>";
        } else {
            $message = "<div class='error'>Invalid verification code. Please try again.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Unsubscribe from XKCD Comics</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&display=swap');
        
        :root {
            --primary-color: #f44336;
            --secondary-color: #ff9800;
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
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 2rem;
            text-align: center;
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

        .form-group {
            margin-bottom: 1.5rem;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
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
        }

        button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Unsubscribe from XKCD Comics</h1>

        <?php if ($message): ?>
            <!-- Show success or error messages -->
            <?php echo $message; ?>
        <?php endif; ?>

        <!-- Step 1: Email input form for unsubscription -->
        <form method="POST">
            <div class="form-group">
                <input type="email" name="unsubscribe_email" required 
                       placeholder="Enter your email" 
                       value="<?php echo htmlspecialchars($email); ?>">
                <button type="submit" id="submit-unsubscribe">Send Verification Code</button>
            </div>
        </form>

        <!-- Step 2: Verification code input form -->
        <form method="POST">
            <div class="form-group">
                <input type="hidden" name="unsubscribe_email" 
                       value="<?php echo htmlspecialchars($email); ?>">
                <input type="text" name="verification_code" maxlength="6" required 
                       placeholder="Enter verification code">
                <button type="submit" id="submit-verification">Verify & Unsubscribe</button>
            </div>
        </form>
    </div>
</body>
</html>
