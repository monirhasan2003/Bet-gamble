<?php
// --- ROBUST SESSION MANAGEMENT ---
// Define a path for our session files inside our website folder
$session_path = __DIR__ . '/sessions';

// Make sure the directory exists
if (!is_dir($session_path)) {
    mkdir($session_path, 0755, true);
}

// Tell PHP to use our custom path to save sessions
ini_set('session.save_path', $session_path);

// Start the session
session_start();
// --- END OF SESSION MANAGEMENT ---

// Include the database connection AFTER starting the session
require_once 'db.php';

// --- SECURITY AND DATA FETCHING ---

// 1. If user is not logged in, redirect them to the login page.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Get the event and option ID from the URL. If they are missing, stop.
if (!isset($_GET['event_id']) || !isset($_GET['option_id'])) {
    die("Error: Missing event or option information.");
}
$event_id = intval($_GET['event_id']);
$option_id = intval($_GET['option_id']);

// 3. Fetch the details for this specific event and option from the database
$stmt = $conn->prepare("
    SELECT e.question, o.option_name, o.payout_multiplier 
    FROM bet_events e 
    JOIN bet_options o ON e.event_id = o.event_id 
    WHERE e.event_id = ? AND o.option_id = ?
");
$stmt->execute([$event_id, $option_id]);
$bet_details = $stmt->fetch(PDO::FETCH_ASSOC);

// If we couldn't find the bet, stop.
if (!$bet_details) {
    die("Error: Could not find the selected bet.");
}

$error_message = '';
$success_message = '';

// --- HANDLE THE BET PLACEMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stake_amount = floatval($_POST['stake_amount']);

    if ($stake_amount <= 0) {
        $error_message = "Please enter a valid amount to bet.";
    } else {
        try {
            $conn->beginTransaction();
            $user_stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE user_id = ? FOR UPDATE");
            $user_stmt->execute([$_SESSION['user_id']]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            $current_balance = $user['wallet_balance'];

            if ($stake_amount > $current_balance) {
                $error_message = "You do not have enough funds for this bet. Your balance is $" . number_format($current_balance, 2);
            } else {
                $new_balance = $current_balance - $stake_amount;
                $update_wallet_stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE user_id = ?");
                $update_wallet_stmt->execute([$new_balance, $_SESSION['user_id']]);
                $potential_payout = $stake_amount * $bet_details['payout_multiplier'];
                $insert_bet_stmt = $conn->prepare(
                    "INSERT INTO user_bets (user_id, event_id, option_id, stake_amount, potential_payout, status) VALUES (?, ?, ?, ?, ?, 'running')"
                );
                $insert_bet_stmt->execute([$_SESSION['user_id'], $event_id, $option_id, $stake_amount, $potential_payout]);
                $bet_id = $conn->lastInsertId();
                $insert_trans_stmt = $conn->prepare(
                    "INSERT INTO transactions (user_id, type, amount, related_bet_id, status) VALUES (?, 'bet_placement', ?, ?, 'completed')"
                );
                $insert_trans_stmt->execute([$_SESSION['user_id'], $stake_amount, $bet_id]);
                $conn->commit();
                $success_message = "Your bet has been placed successfully!";
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "An error occurred. Please try again. Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Bet - BetGamble</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">
    <?php require_once 'header.php'; // We still include the header for the nav bar ?>
    <main>
        <div class="max-w-2xl mx-auto py-16 px-4">
            <div class="bg-white p-8 rounded-lg shadow-lg">
                <a href="index.php" class="text-indigo-600 hover:underline mb-6 inline-block">&larr; Back to Live Events</a>
                <h1 class="text-2xl font-bold text-gray-900">Place Your Bet</h1>

                <div class="mt-4 border-t border-b border-gray-200 py-4">
                    <p class="text-gray-500">Event:</p>
                    <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($bet_details['question']); ?></p>
                </div>
                <div class="border-b border-gray-200 py-4">
                    <p class="text-gray-500">Your Prediction:</p>
                    <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($bet_details['option_name']); ?></p>
                </div>
                <div class="border-b border-gray-200 py-4">
                    <p class="text-gray-500">Payout Multiplier:</p>
                    <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($bet_details['payout_multiplier']); ?>x</p>
                </div>

                <?php if(!empty($success_message)): ?>
                    <div class="mt-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md">
                        <p class="font-bold">Success!</p>
                        <p><?php echo $success_message; ?></p>
                    </div>
                <?php else: ?>
                    <form action="" method="POST" class="mt-6">
                        <?php if(!empty($error_message)): ?>
                            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md">
                                <p><?php echo $error_message; ?></p>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label for="stake_amount" class="block text-sm font-medium text-gray-700">Enter Bet Amount ($)</label>
                            <div class="mt-1">
                                <input type="text" name="stake_amount" id="stake_amount" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-3" placeholder="0.00">
                            </div>
                        </div>
                        <button type="submit" class="mt-6 w-full inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                            Confirm Bet
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>