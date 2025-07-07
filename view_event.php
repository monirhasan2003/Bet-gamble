<?php
require_once 'header.php'; // Handles session, DB connection, and nav bar

// --- SECURITY AND DATA FETCHING ---

// 1. Get the event ID from the URL. If it's missing or not a number, stop.
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    die("Error: Invalid Event ID.");
}
$event_id = intval($_GET['event_id']);

// 2. Fetch the main event details
$event_stmt = $conn->prepare("SELECT * FROM bet_events WHERE event_id = ? AND status = 'open' AND closes_at > NOW()");
$event_stmt->execute([$event_id]);
$event = $event_stmt->fetch(PDO::FETCH_ASSOC);

// If the event doesn't exist or isn't open for betting, stop.
if (!$event) {
    die("Error: This event could not be found or is no longer open for betting.");
}

// 3. Fetch all the options for this specific event
$options_stmt = $conn->prepare("SELECT * FROM bet_options WHERE event_id = ?");
$options_stmt->execute([$event_id]);
$options = $options_stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Calculate the total pool and individual stakes for this event
$total_pool_stmt = $conn->prepare("SELECT SUM(stake_amount) FROM user_bets WHERE event_id = ?");
$total_pool_stmt->execute([$event_id]);
$total_pool = $total_pool_stmt->fetchColumn() ?: 0;

$staked_stmt = $conn->prepare("SELECT option_id, SUM(stake_amount) as total_staked FROM user_bets WHERE event_id = ? GROUP BY option_id");
$staked_stmt->execute([$event_id]);
$staked_by_option = $staked_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// --- END OF DATA FETCHING ---

$error_message = '';
$success_message = '';

// --- HANDLE BET PLACEMENT FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_bet'])) {
    // Make sure the user is logged in to place a bet
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $stake_amount = floatval($_POST['stake_amount']);
    $option_id_to_bet_on = intval($_POST['option_id']);

    if ($stake_amount <= 0 || empty($option_id_to_bet_on)) {
        $error_message = "Please select an option and enter a valid amount to bet.";
    } else {
        // This is the same secure transaction logic from our old place_bet.php file
        try {
            $conn->beginTransaction();
            $user_stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE user_id = ? FOR UPDATE");
            $user_stmt->execute([$_SESSION['user_id']]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            $current_balance = $user['wallet_balance'];

            if ($stake_amount > $current_balance) {
                $error_message = "You do not have enough funds for this bet. Your balance is $" . number_format($current_balance, 2);
            } else {
                $payout_multiplier_stmt = $conn->prepare("SELECT payout_multiplier FROM bet_options WHERE option_id = ?");
                $payout_multiplier_stmt->execute([$option_id_to_bet_on]);
                $payout_multiplier = $payout_multiplier_stmt->fetchColumn();

                $new_balance = $current_balance - $stake_amount;
                $update_wallet_stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE user_id = ?");
                $update_wallet_stmt->execute([$new_balance, $_SESSION['user_id']]);
                
                $potential_payout = $stake_amount * $payout_multiplier;
                
                $insert_bet_stmt = $conn->prepare("INSERT INTO user_bets (user_id, event_id, option_id, stake_amount, potential_payout, status) VALUES (?, ?, ?, ?, ?, 'running')");
                $insert_bet_stmt->execute([$_SESSION['user_id'], $event_id, $option_id_to_bet_on, $stake_amount, $potential_payout]);
                $bet_id = $conn->lastInsertId();
                
                $insert_trans_stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, related_bet_id, status) VALUES (?, 'bet_placement', ?, ?, 'completed')");
                $insert_trans_stmt->execute([$_SESSION['user_id'], $stake_amount, $bet_id]);
                
                $conn->commit();
                $success_message = "Your bet has been placed successfully! You will be redirected shortly.";
                // Redirect back to the same page after a few seconds to show updated stats
                header("Refresh:3; url=view_event.php?event_id=$event_id");
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "An error occurred. Please try again.";
        }
    }
}
?>

<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-lg">
        <a href="index.php" class="text-indigo-600 hover:underline mb-6 inline-block">&larr; Back to All Events</a>

        <!-- Event Details -->
        <div class="border-b pb-4 mb-6">
            <p class="text-red-600 font-semibold countdown-timer" data-closes-at="<?php echo htmlspecialchars($event['closes_at']); ?>">Calculating...</p>
            <h1 class="text-3xl font-bold text-gray-900 mt-2"><?php echo htmlspecialchars($event['question']); ?></h1>
            <p class="mt-2 text-gray-500">Total Pool: <span class="font-bold">$<?php echo number_format($total_pool, 2); ?></span></p>
        </div>

        <!-- Betting Options & Progress Bars -->
        <div class="space-y-4">
            <?php foreach ($options as $option): ?>
                <?php
                    $color_class = 'bg-indigo-600';
                    if (strtolower($option['option_name']) == 'yes') { $color_class = 'bg-green-500'; }
                    elseif (strtolower($option['option_name']) == 'no') { $color_class = 'bg-red-500'; }
                    $staked_on_option = isset($staked_by_option[$option['option_id']]) ? $staked_by_option[$option['option_id']] : 0;
                    $percentage = ($total_pool > 0) ? ($staked_on_option / $total_pool) * 100 : 0;
                ?>
                <div class="p-4 border rounded-lg">
                    <div class="flex justify-between items-center text-lg font-bold">
                        <p class="text-gray-800"><?php echo htmlspecialchars($option['option_name']); ?></p>
                        <p class="text-gray-600"><?php echo round($percentage); ?>%</p>
                    </div>
                    <div class="progress-bar-container mt-2">
                        <div class="progress-bar-fill <?php echo $color_class; ?>" style="width: <?php echo $percentage; ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Bet Placement Form -->
        <form action="view_event.php?event_id=<?php echo $event_id; ?>" method="POST" class="mt-8 border-t pt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Place Your Bet</h2>
            
            <?php if(!empty($success_message)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md">
                    <p class="font-bold">Success!</p>
                    <p><?php echo $success_message; ?></p>
                </div>
            <?php elseif(isset($_SESSION['user_id'])): ?>
                <?php if(!empty($error_message)): ?>
                    <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md">
                        <p><?php echo $error_message; ?></p>
                    </div>
                <?php endif; ?>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label for="option_id" class="block text-sm font-medium text-gray-700">Choose an Option</label>
                        <select name="option_id" id="option_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <?php foreach ($options as $option): ?>
                                <option value="<?php echo $option['option_id']; ?>"><?php echo htmlspecialchars($option['option_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="stake_amount" class="block text-sm font-medium text-gray-700">Amount ($)</label>
                        <input type="text" name="stake_amount" id="stake_amount" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-2" placeholder="0.00">
                    </div>
                </div>
                <button type="submit" name="place_bet" class="mt-4 w-full inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    Confirm Bet
                </button>
            <?php else: ?>
                <div class="text-center p-4 bg-gray-100 rounded-md">
                    <p><a href="login.php" class="font-bold text-indigo-600 hover:underline">Log in</a> or <a href="register.php" class="font-bold text-indigo-600 hover:underline">register</a> to place a bet.</p>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- JAVASCRIPT FOR THE COUNTDOWN TIMER (Same as index.php) -->
<script>
    function initializeCountdown() {
        const timerElements = document.querySelectorAll('.countdown-timer');
        timerElements.forEach(timerElement => {
            const closesAt = new Date(timerElement.dataset.closesAt.replace(' ', 'T')).getTime();
            const interval = setInterval(() => {
                const now = new Date().getTime();
                const distance = closesAt - now;
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                if (distance < 0) {
                    clearInterval(interval);
                    timerElement.innerHTML = "Betting Closed";
                } else {
                    timerElement.innerHTML = `Closes in: ${days}d ${hours}h ${minutes}m ${seconds}s`;
                }
            }, 1000);
        });
    }
    window.addEventListener('load', initializeCountdown);
</script>

</main>
</body>
</html>
