<?php
require_once 'header.php';

$error_message = '';
$success_message = '';
$token = $_GET['token'] ?? '';
$show_form = false;

if (empty($token)) {
    $error_message = "Invalid password reset link. Please request a new one.";
} else {
    // Check if the token is valid and not expired
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $reset_request = $stmt->fetch();

    if ($reset_request) {
        // Token is valid, show the password reset form
        $show_form = true;
    } else {
        $error_message = "This password reset link is invalid or has expired. Please request a new one.";
    }
}

// Handle the form submission for the new password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $token_from_form = $_POST['token'];

    // Re-verify token to be safe
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token_from_form]);
    $reset_request = $stmt->fetch();

    if (!$reset_request) {
        $error_message = "Your session has expired. Please request a new reset link.";
        $show_form = false;
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error_message = "Please fill in both password fields.";
        $show_form = true;
    } elseif ($new_password !== $confirm_password) {
        $error_message = "The new passwords do not match.";
        $show_form = true;
    } else {
        // All checks passed, we can now update the password
        $conn->beginTransaction();
        try {
            // 1. Hash the new password
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            // 2. Update the user's password in the 'users' table
            $email = $reset_request['email'];
            $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $update_stmt->execute([$new_password_hash, $email]);

            // 3. Delete the token from the 'password_resets' table so it can't be used again
            $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $delete_stmt->execute([$email]);

            $conn->commit();
            $success_message = "Your password has been reset successfully! You can now log in with your new password.";
            $show_form = false;

        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "An error occurred. Please try again.";
            $show_form = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Bet Gamble</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="form-container w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold tracking-wider">Reset Your Password</h1>
        </div>
        
        <?php if(!empty($error_message)): ?>
            <div class="alert-box alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if(!empty($success_message)): ?>
            <div class="alert-box alert-success"><?php echo $success_message; ?></div>
            <a href="login.php" class="mt-4 block text-center font-medium text-white hover:underline">Click here to Login</a>
        <?php endif; ?>

        <?php if ($show_form): ?>
            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="post" class="space-y-6">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div>
                    <label for="new_password" class="block mb-2 text-sm font-medium text-gray-300">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="form-input" placeholder="Enter your new password" required>
                </div>
                 <div>
                    <label for="confirm_password" class="block mb-2 text-sm font-medium text-gray-300">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-input" placeholder="Confirm your new password" required>
                </div>
                <button type="submit" name="reset_password" class="btn-gradient">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
