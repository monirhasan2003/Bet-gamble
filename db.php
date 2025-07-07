<?php
// File: db.php
// This file connects to your database.
// Create this file once and include it in your other PHP files.

// -- IMPORTANT --
// Replace with your actual database credentials from cPanel.
$servername = "localhost";
$username = "investo3_betuser"; // e.g., betgamble_user
$password = "Zarra-01718852882";
$dbname = "investo3_betgamble"; // e.g., betgamble_db

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // In a real application, you would log this error, not show it to the user.
    die("Connection failed: " . $e->getMessage());
}
?>