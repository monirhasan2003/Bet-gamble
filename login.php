<?php
ob_start();
require_once 'header.php';

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}
$error_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    if (empty($username) || empty($password)) {
        $error_message = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password_hash FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION["user_id"] = $user['user_id'];
                $_SESSION["username"] = $user['username'];
                if ($user['user_id'] == 1) {
                    header("Location: admin/index.php");
                } else {
                    header("Location: dashboard.php");
                }
                ob_end_flush();
                exit;
            }
        }
        $error_message = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bet Gamble</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="form-container w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold tracking-wider">Bet Gamble</h1>
            <p class="text-gray-300 text-sm">Welcome Back</p>
        </div>
        
        <?php if(!empty($error_message)): ?>
            <div class="alert-box alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="login.php" method="post" class="space-y-6">
            <div>
                <label for="username" class="block mb-2 text-sm font-medium text-gray-300">Username</label>
                <input type="text" name="username" id="username" class="form-input" placeholder="Your username" required>
            </div>
            <div>
                <div class="flex justify-between items-center">
                    <label for="password" class="block text-sm font-medium text-gray-300">Password</label>
                    <!-- NEW: Forgot Password Link -->
                    <a href="forgot_password.php" class="text-sm text-gray-400 hover:text-white">Forgot Password?</a>
                </div>
                <input type="password" name="password" id="password" class="mt-1 form-input" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-gradient">Login</button>
        </form>
        
        <p class="text-center mt-8 text-sm text-gray-400">
            Don't have an account? <a href="register.php" class="font-medium text-white hover:underline">Register here</a>
        </p>
    </div>
</body>
</html>
