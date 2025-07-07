<?php
// File: register.php
// This file handles the user registration form and logic.

// Include the database connection file
require_once 'db.php';

$error_message = '';
$success_message = '';

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $error_message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error_message = "Username or email already taken.";
            } else {
                // Hash the password for security
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                // Insert the new user into the database
                $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password_hash', $password_hash);

                if ($stmt->execute()) {
                    $success_message = "Registration successful! You can now login.";
                } else {
                    $error_message = "Something went wrong. Please try again.";
                }
            }
        } catch(PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bet Gamble</title>
    <!-- We still use Tailwind for basic layout, but our CSS will override styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <body class="auth-page">
    <div class="form-container w-full max-w-md">
        <!-- Logo Placeholder -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold tracking-wider">BetGamble</h1>
            <p class="text-gray-300 text-sm">Create Your Account</p>
        </div>
        
        <?php if(!empty($error_message)): ?>
            <div class="alert-box alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if(!empty($success_message)): ?>
            <div class="alert-box alert-success"><?php echo $success_message; ?></div>
        <?php else: ?>
            <form action="register.php" method="post" class="space-y-6">
                <div>
                    <label for="username" class="block mb-2 text-sm font-medium text-gray-300">Username</label>
                    <input type="text" name="username" id="username" class="form-input" placeholder="e.g., johndoe" required>
                </div>
                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-300">Email</label>
                    <input type="email" name="email" id="email" class="form-input" placeholder="you@example.com" required>
                </div>
                <div>
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-300">Password</label>
                    <input type="password" name="password" id="password" class="form-input" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-gradient">Create Account</button>
            </form>
        <?php endif; ?>
        
        <p class="text-center mt-8 text-sm text-gray-400">
            Already have an account? <a href="login.php" class="font-medium text-white hover:underline">Login here</a>
        </p>
    </div>
</body>
</html>