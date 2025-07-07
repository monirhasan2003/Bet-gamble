<?php
require_once 'header.php'; // Handles session, DB connection, and nav bar

// Security Check: If user is not logged in, send them away.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// --- DATA FETCHING ---
// Get all transactions for the logged-in user, ordering by the most recent first.
$trans_stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
$trans_stmt->execute([$user_id]);
$transactions = $trans_stmt->fetchAll(PDO::FETCH_ASSOC);
// --- END OF DATA FETCHING ---
?>

<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-lg">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Transaction History</h1>
                <p class="mt-1 text-sm text-gray-500">A complete log of all your financial activity.</p>
            </div>
            <a href="dashboard.php" class="mt-4 sm:mt-0 inline-block text-indigo-600 hover:underline">&larr; Back to Dashboard</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">You have no transactions yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $trans): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date("M j, Y g:i a", strtotime($trans['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $trans['type']))); ?>
                                </td>
                                <?php
                                    // Set color based on transaction type
                                    $amount_class = 'text-gray-800'; // Default
                                    $amount_prefix = '';
                                    if ($trans['type'] == 'deposit' || $trans['type'] == 'bet_win') {
                                        $amount_class = 'text-green-600 font-semibold';
                                        $amount_prefix = '+';
                                    } elseif ($trans['type'] == 'withdrawal' || $trans['type'] == 'bet_placement') {
                                        $amount_class = 'text-red-600 font-semibold';
                                        $amount_prefix = '-';
                                    }
                                ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $amount_class; ?>">
                                    <?php echo $amount_prefix; ?>$<?php echo number_format($trans['amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $trans['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($trans['status'])); ?>
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
