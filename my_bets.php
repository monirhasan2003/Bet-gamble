<?php
require_once 'header.php'; // Handles session, DB connection, and nav bar

// Security Check: If user is not logged in, send them away.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// --- DATA FETCHING ---
// This is a more complex query. It joins three tables together to get all the info we need.
$bets_stmt = $conn->prepare("
    SELECT 
        ub.stake_amount,
        ub.potential_payout,
        ub.status,
        ub.placed_at,
        be.question,
        bo.option_name
    FROM 
        user_bets AS ub
    JOIN 
        bet_events AS be ON ub.event_id = be.event_id
    JOIN 
        bet_options AS bo ON ub.option_id = bo.option_id
    WHERE 
        ub.user_id = ?
    ORDER BY 
        ub.placed_at DESC
");
$bets_stmt->execute([$user_id]);
$my_bets = $bets_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- END OF DATA FETCHING ---
?>

<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-lg">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Betting History</h1>
                <p class="mt-1 text-sm text-gray-500">A complete record of all your wagers.</p>
            </div>
            <a href="dashboard.php" class="mt-4 sm:mt-0 inline-block text-indigo-600 hover:underline">&larr; Back to Dashboard</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Question</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Your Bet</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stake</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Potential Payout</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Placed</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($my_bets)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">You have not placed any bets yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($my_bets as $bet): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($bet['question']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($bet['option_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($bet['stake_amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($bet['potential_payout'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date("M j, Y g:i a", strtotime($bet['placed_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php
                                        // Use our new CSS classes for the status badge
                                        $status_class = 'status-' . strtolower($bet['status']);
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($bet['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</main>
</body>
</html>
