<?php
require_once 'header.php'; // Handles session and DB connection

// Security Check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $withdraw_amount = floatval($_POST['withdraw_amount']);
    $payment_method = trim($_POST['payment_method']);
    $details = [];

    // Collect details based on the selected payment method
    if ($payment_method === 'crypto') {
        $details['address'] = trim($_POST['crypto_address']);
        $details['network'] = trim($_POST['crypto_network']);
        if (empty($details['address']) || empty($details['network'])) {
            $error_message = "Wallet Address and Network are required for crypto withdrawals.";
        }
    } elseif ($payment_method === 'bank') {
        $details['bank_name'] = trim($_POST['bank_name']);
        $details['account_name'] = trim($_POST['account_name']);
        $details['account_number'] = trim($_POST['account_number']);
        if (empty($details['bank_name']) || empty($details['account_name']) || empty($details['account_number'])) {
            $error_message = "All bank details are required.";
        }
    } elseif ($payment_method === 'mobile') {
        $details['service'] = trim($_POST['mobile_service']);
        $details['account_number'] = trim($_POST['mobile_number']);
        if (empty($details['service']) || empty($details['account_number'])) {
            $error_message = "Service and Account Number are required for mobile banking.";
        }
    } else {
        $error_message = "Invalid payment method selected.";
    }

    if ($withdraw_amount <= 0) {
        $error_message = "Please enter a valid amount to withdraw.";
    }

    // If there are no errors so far, proceed with the transaction
    if (empty($error_message)) {
        try {
            $conn->beginTransaction();
            $user_stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE user_id = ? FOR UPDATE");
            $user_stmt->execute([$user_id]);
            $current_balance = $user_stmt->fetchColumn();

            if ($withdraw_amount > $current_balance) {
                $error_message = "You cannot withdraw more than your current balance of $" . number_format($current_balance, 2);
            } else {
                $new_balance = $current_balance - $withdraw_amount;
                $update_wallet_stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE user_id = ?");
                $update_wallet_stmt->execute([$new_balance, $user_id]);

                // Convert the details array into a JSON string to store in the database
                $withdrawal_details_json = json_encode($details);

                $insert_trans_stmt = $conn->prepare(
                    "INSERT INTO transactions (user_id, type, amount, status, payment_method, withdrawal_details) VALUES (?, 'withdrawal', ?, 'pending', ?, ?)"
                );
                $insert_trans_stmt->execute([$user_id, $withdraw_amount, $payment_method, $withdrawal_details_json]);

                $conn->commit();
                $success_message = "Your withdrawal request for $" . number_format($withdraw_amount, 2) . " has been submitted. It will be processed shortly.";
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "An error occurred. Please try again.";
        }
    }
}
?>

<div class="max-w-2xl mx-auto py-16 px-4">
    <div class="bg-white p-8 rounded-lg shadow-lg">
        <a href="dashboard.php" class="text-indigo-600 hover:underline mb-6 inline-block">&larr; Back to Dashboard</a>
        <h1 class="text-2xl font-bold text-gray-900">Request Withdrawal</h1>
        <p class="mt-2 text-gray-600">Select your preferred withdrawal method and fill in the details.</p>
        
        <?php if(!empty($success_message)): ?>
            <meta http-equiv="refresh" content="4;url=dashboard.php">
            <div class="mt-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md">
                <p class="font-bold">Request Submitted!</p>
                <p><?php echo $success_message; ?></p>
                <p class="mt-2 text-sm">You will be redirected back to the dashboard shortly...</p>
            </div>
        <?php else: ?>
            <form id="withdrawalForm" action="withdraw.php" method="POST" class="mt-6 space-y-6">
                <?php if(!empty($error_message)): ?>
                    <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md">
                        <p><?php echo $error_message; ?></p>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="withdraw_amount" class="block text-sm font-medium text-gray-700">Withdrawal Amount ($)</label>
                    <input type="text" name="withdraw_amount" id="withdraw_amount" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" placeholder="0.00" required>
                </div>

                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700">Withdrawal Method</label>
                    <select name="payment_method" id="payment_method" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" required>
                        <option value="">-- Select a Method --</option>
                        <option value="crypto">Cryptocurrency</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="mobile">Mobile Banking (bKash, Nagad, etc.)</option>
                    </select>
                </div>
                
                <!-- Dynamic fields will be injected here by JavaScript -->
                <div id="method-details" class="space-y-6"></div>

                <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-red-500 hover:bg-red-600">
                    Submit Withdrawal Request
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- JAVASCRIPT for the dynamic form -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethodSelect = document.getElementById('payment_method');
        const detailsContainer = document.getElementById('method-details');

        const cryptoFields = `
            <div>
                <label for="crypto_address" class="block text-sm font-medium text-gray-700">Your Wallet Address</label>
                <input type="text" name="crypto_address" id="crypto_address" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" placeholder="e.g., 0x123..." required>
            </div>
            <div>
                <label for="crypto_network" class="block text-sm font-medium text-gray-700">Network</label>
                <input type="text" name="crypto_network" id="crypto_network" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" placeholder="e.g., ERC-20, BEP-20, Solana" required>
            </div>
        `;

        const bankFields = `
            <div>
                <label for="bank_name" class="block text-sm font-medium text-gray-700">Bank Name</label>
                <input type="text" name="bank_name" id="bank_name" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" required>
            </div>
            <div>
                <label for="account_name" class="block text-sm font-medium text-gray-700">Account Holder Name</label>
                <input type="text" name="account_name" id="account_name" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" required>
            </div>
            <div>
                <label for="account_number" class="block text-sm font-medium text-gray-700">Account Number</label>
                <input type="text" name="account_number" id="account_number" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" required>
            </div>
        `;

        const mobileFields = `
             <div>
                <label for="mobile_service" class="block text-sm font-medium text-gray-700">Service Name</label>
                <input type="text" name="mobile_service" id="mobile_service" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" placeholder="e.g., bKash, Nagad, Rocket" required>
            </div>
            <div>
                <label for="mobile_number" class="block text-sm font-medium text-gray-700">Account Number</label>
                <input type="text" name="mobile_number" id="mobile_number" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" required>
            </div>
        `;

        paymentMethodSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            detailsContainer.innerHTML = ''; // Clear previous fields

            if (selectedValue === 'crypto') {
                detailsContainer.innerHTML = cryptoFields;
            } else if (selectedValue === 'bank') {
                detailsContainer.innerHTML = bankFields;
            } else if (selectedValue === 'mobile') {
                detailsContainer.innerHTML = mobileFields;
            }
        });
    });
</script>

</main>
</body>
</html>
