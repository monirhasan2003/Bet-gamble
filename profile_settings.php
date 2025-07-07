<?php
require_once 'header.php';

// Security Check: If user is not logged in, send them away.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$profile_error = '';
$profile_success = '';
$password_error = '';
$password_success = '';

// --- HANDLE PROFILE UPDATE FORM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);

    if (empty($new_username) || empty($new_email)) {
        $profile_error = "Username and Email cannot be empty.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $profile_error = "Invalid email format.";
    } else {
        // Check if the new username or email is already taken by ANOTHER user
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $check_stmt->execute([$new_username, $new_email, $user_id]);
        if ($check_stmt->fetch()) {
            $profile_error = "That username or email is already in use by another account.";
        } else {
            // All checks passed, update the user's details
            $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
            $update_stmt->execute([$new_username, $new_email, $user_id]);
            
            // IMPORTANT: Update the session variable so the name changes instantly in the header
            $_SESSION['username'] = $new_username;
            $profile_success = "Profile updated successfully!";
        }
    }
}

// --- HANDLE PASSWORD CHANGE FORM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match.";
    } else {
        // Get the user's current hashed password from the database
        $user_stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $user_stmt->execute([$user_id]);
        $current_hash = $user_stmt->fetchColumn();

        // Verify the provided current password against the stored hash
        if (password_verify($current_password, $current_hash)) {
            // Current password is correct, hash the new password
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Update the database with the new hash
            $update_pass_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $update_pass_stmt->execute([$new_password_hash, $user_id]);
            
            $password_success = "Password changed successfully!";
        } else {
            $password_error = "Your current password was incorrect.";
        }
    }
}

// Fetch current user data to pre-fill the form
$user_data_stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
$user_data_stmt->execute([$user_id]);
$user_data = $user_data_stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Profile & Settings</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Edit Profile Form -->
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Edit Profile</h2>
            <form action="profile_settings.php" method="POST" class="space-y-6">
                <?php if(!empty($profile_error)): ?><div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?php echo $profile_error; ?></div><?php endif; ?>
                <?php if(!empty($profile_success)): ?><div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg"><?php echo $profile_success; ?></div><?php endif; ?>
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3">
                </div>
                <button type="submit" name="update_profile" class="w-full inline-flex justify-center py-3 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Save Profile Changes
                </button>
            </form>
        </div>

        <!-- Change Password Form -->
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Change Password</h2>
            <form action="profile_settings.php" method="POST" class="space-y-6">
                <?php if(!empty($password_error)): ?><div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?php echo $password_error; ?></div><?php endif; ?>
                <?php if(!empty($password_success)): ?><div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg"><?php echo $password_success; ?></div><?php endif; ?>

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3">
                </div>
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3">
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3">
                </div>
                <button type="submit" name="change_password" class="w-full inline-flex justify-center py-3 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-700 hover:bg-gray-800">
                    Change Password
                </button>
            </form>
        </div>
    </div>
</div>

</main>
</body>
</html>
