<?php
// --- ROBUST SESSION MANAGEMENT ---
// This ensures we are targeting the correct session to destroy it.
$session_path = __DIR__ . '/sessions';
ini_set('session.save_path', $session_path);
session_start();
// --- END OF SESSION MANAGEMENT ---

// Unset all of the session variables.
$_SESSION = array();

// If you want to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to the login page.
header("location: login.php");
exit;
?>
