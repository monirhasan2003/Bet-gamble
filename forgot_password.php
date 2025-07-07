<?php
require_once 'header.php';

$message = '';
$is_error = false;
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $is_error = true;
    } else {
        // Check if the email exists in the users table
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // User exists, generate a secure token
            $token = bin2hex(random_bytes(50));
            $expires = new DateTime('+1 hour');
            $expires_at = $expires->format('Y-m-d H:i:s');

            // Store the token in our new password_resets table
            $insert_stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $insert_stmt->execute([$email, $token, $expires_at]);

            // --- SIMULATE SENDING EMAIL ---
            // In a real site, you would email this link. For our project, we will display it.
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $path = dirname($_SERVER['PHP_SELF']);
            $reset_link = "{$protocol}://{$host}{$path}/reset_password.php?token={$token}";
            
            $message = "A password reset link has been generated. In a real site, this would be emailed to you.";
            
        } else {
            // Email not found, but we show a generic message for security.
            $message = "If an account with that email exists, a reset link has been generated.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Bet Gamble</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="form-container w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold tracking-wider">Forgot Password</h1>
            <p class="text-gray-300 text-sm">Enter your email to receive a reset link.</p>
        </div>
        
        <?php if(!empty($message)): ?>
            <div class="alert-box <?php echo $is_error ? 'alert-error' : 'alert-success'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!empty($reset_link)): ?>
            <div class="mt-4 p-4 bg-gray-900/50 rounded-lg">
                <p class="text-white font-semibold">Your Reset Link:</p>
                <p class="text-yellow-400 break-all text-sm mt-2"><?php echo htmlspecialchars($reset_link); ?></p>
                <p class="text-xs text-gray-400 mt-2">Click the link above to proceed.</p>
            </div>
        <?php else: ?>
            <form action="forgot_password.php" method="post" class="space-y-6">
                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-300">Your Email Address</label>
                    <input type="email" name="email" id="email" class="form-input" placeholder="you@example.com" required>
                </div>
                <button type="submit" class="btn-gradient">Send Reset Link</button>
            </form>
        <?php endif; ?>
        
        <p class="text-center mt-8 text-sm text-gray-400">
            Remember your password? <a href="login.php" class="font-medium text-white hover:underline">Login here</a>
        </p>
    </div>
</body>
</html>
