<?php
require_once 'header.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

// Fetch user's wallet balance
$stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallet_balance = $stmt->fetchColumn();

// Fetch last 4 transactions for the sidebar
$recent_trans_stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
$recent_trans_stmt->execute([$user_id]);
$recent_transactions = $recent_trans_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Add the special class to the body tag -->
<body class="client-dashboard-body">

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Welcome Header -->
    <div class="mb-8">
        <!-- UPDATED TEXT COLORS -->
        <h1 class="text-3xl font-bold text-white">Welcome back, <?php echo htmlspecialchars($username); ?>!</h1>
        <p class="text-gray-300">Here's your account overview.</p>
    </div>

    <div class="dashboard-grid">
        <!-- Main Content Column -->
        <div class="dashboard-main-content space-y-6">
            <!-- Balance Card -->
            <div class="dashboard-card balance-card">
                <p class="text-lg font-medium opacity-80">Current Balance</p>
                <p class="text-5xl font-bold mt-2 tracking-tight">$<?php echo number_format($wallet_balance, 2); ?></p>
            </div>

            <!-- Action Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="dashboard-card action-card">
                    <h3>Add Funds</h3>
                    <p>Make a deposit to your wallet.</p>
                    <a href="deposit.php" class="btn btn-green">Deposit Now</a>
                </div>
                 <div class="dashboard-card action-card">
                    <h3>Withdraw Funds</h3>
                    <p>Request a withdrawal from your wallet.</p>
                    <a href="withdraw.php" class="btn btn-red">Withdraw Now</a>
                </div>
            </div>
             <div class="dashboard-card action-card">
                <h3>Betting History</h3>
                <p>View your active and past bets.</p>
                <a href="my_bets.php" class="btn btn-blue">View My Bets</a>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="dashboard-sidebar">
            <div class="dashboard-card">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
                <ul class="activity-list">
                    <?php if (empty($recent_transactions)): ?>
                        <li>No recent activity.</li>
                    <?php else: ?>
                        <?php foreach ($recent_transactions as $trans): ?>
                            <li>
                                <div>
                                    <p class="font-semibold text-gray-700"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $trans['type']))); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo date("M j, g:i a", strtotime($trans['created_at'])); ?></p>
                                </div>
                                <?php
                                    $amount_class = 'text-gray-800';
                                    $amount_prefix = '';
                                    if (in_array($trans['type'], ['deposit', 'bet_win', 'bonus'])) {
                                        $amount_class = 'text-green-600';
                                        $amount_prefix = '+';
                                    } elseif (in_array($trans['type'], ['withdrawal', 'bet_placement'])) {
                                        $amount_class = 'text-red-600';
                                        $amount_prefix = '-';
                                    }
                                ?>
                                <p class="font-bold font-mono <?php echo $amount_class; ?>"><?php echo $amount_prefix; ?>$<?php echo number_format($trans['amount'], 2); ?></p>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <a href="transactions.php" class="block text-center mt-6 text-indigo-600 hover:underline text-sm font-medium">View All Transactions</a>
            </div>
        </div>
    </div>
</div>

</main>
</body>
</html>
