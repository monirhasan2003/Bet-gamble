<?php
require_once 'header.php'; // Handles session, DB connection, and nav bar

// Security Check: If user is not logged in, send them away.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// --- IMPORTANT: SET YOUR PAYMENT DETAILS HERE ---
$your_bkash_number = "017XXXXXXXX";     // Replace with your bKash merchant/personal number
$your_nagad_number = "018XXXXXXXX";     // Replace with your Nagad merchant/personal number
$your_deepay_id = "your-deepay-id";       // This is not used for the button, but good to have
$your_metamask_address = "0x29DE0a5638E6573eAe145aC85D90B79771789667"; // Your MetaMask wallet address

// Handle the form submission when the user confirms their MANUAL deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_manual_deposit'])) {
    $deposit_amount = floatval($_POST['amount']);
    $transaction_id = trim($_POST['transaction_id']);
    $payment_method = trim($_POST['payment_method']);

    if ($deposit_amount <= 0 || empty($transaction_id) || empty($payment_method)) {
        $error_message = "Please enter a valid amount and transaction ID.";
    } else {
        try {
            // Create a 'pending' transaction for the admin to approve.
            $insert_trans_stmt = $conn->prepare(
                "INSERT INTO transactions (user_id, type, amount, status, payment_method, withdrawal_details) VALUES (?, 'deposit', ?, 'pending', ?, ?)"
            );
            // We store the user-provided Transaction ID in the 'withdrawal_details' column.
            $insert_trans_stmt->execute([$user_id, $deposit_amount, $payment_method, $transaction_id]);

            $success_message = "Your deposit request has been submitted for review. The funds will be added to your account after admin confirmation.";

        } catch (Exception $e) {
            $error_message = "An error occurred. Please try again.";
        }
    }
}
?>
<!-- NEW: Added the 'client-dashboard-body' class to fix the background color -->
<body class="client-dashboard-body">

<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-lg">
        <a href="dashboard.php" class="text-indigo-600 hover:underline mb-6 inline-block">&larr; Back to Dashboard</a>
        <h1 class="text-2xl font-bold text-gray-900">Make a Deposit</h1>
        <p class="mt-2 text-gray-600">Select a payment method to add funds to your wallet.</p>

        <?php if(!empty($success_message)): ?>
            <div class="mt-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md">
                <p class="font-bold">Request Submitted!</p>
                <p><?php echo $success_message; ?></p>
            </div>
        <?php else: ?>
            <!-- Step 1: Method Selection -->
            <div class="mt-6">
                <label for="payment_method" class="block text-sm font-medium text-gray-700">Choose Deposit Method</label>
                <select id="payment_method" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="">-- Select a Method --</option>
                    <option value="depay">DePay (Crypto)</option>
                    <option value="metamask">MetaMask (Manual Crypto)</option>
                    <option value="bkash">bKash</option>
                    <option value="nagad">Nagad</option>
                </select>
            </div>

            <!-- Step 2: Instructions and Form (hidden by default) -->
            <div id="deposit-instructions" class="hidden mt-6 border-t pt-6">
                <!-- DePay Content -->
                <div id="content-depay" class="hidden tab-content space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800">Deposit with DePay</h3>
                    <p class="text-gray-600">Click the button below to pay with any cryptocurrency. The DePay widget will open and guide you through the process.</p>
                    <div class="my-6">
                        <!-- OFFICIAL DEPAY BUTTON CODE -->
                        <div
                          class="DePayButton"
                          label="Pay with Crypto"
                          integration="2f3cbb13-1065-448c-9822-9d64f93a33e5"
                          blockchains='["ethereum", "bep20"]'
                        ></div>
                        <script src="https://integrate.depay.com/buttons/v13.js"></script>
                        <noscript><a href="https://depay.com">Web3 Payments</a> are only supported with JavaScript enabled.</noscript>
                        <script>DePayButtons.init({document: document});</script>
                    </div>
                    <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400">
                        <p class="font-bold text-yellow-800">Important:</p>
                        <p class="text-yellow-700">After your payment is successful, please submit the details below for manual confirmation by our team.</p>
                    </div>
                     <form action="deposit.php" method="POST" class="mt-4 space-y-4">
                        <input type="hidden" name="payment_method" value="DePay">
                        <div>
                            <label for="amount_depay" class="block text-sm font-medium text-gray-700">Amount Sent ($)</label>
                            <input type="text" name="amount" id="amount_depay" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" placeholder="e.g., 50.00" required>
                        </div>
                        <div>
                            <label for="transaction_id_depay" class="block text-sm font-medium text-gray-700">Transaction Hash (TxID)</label>
                            <input type="text" name="transaction_id" id="transaction_id_depay" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" placeholder="e.g., 0x123abc..." required>
                        </div>
                        <button type="submit" name="confirm_manual_deposit" class="w-full inline-flex justify-center py-3 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Submit Deposit for Review
                        </button>
                    </form>
                </div>

                <!-- MetaMask Content -->
                 <div id="content-metamask" class="hidden tab-content space-y-4">
                     <h3 class="text-lg font-semibold text-gray-800">Manual Crypto Deposit Instructions</h3>
                    <p>1. Open your MetaMask wallet and ensure you are on the correct network (e.g., Ethereum Mainnet).</p>
                    <p>2. Send your desired deposit amount to this address: <strong class="font-mono text-sm sm:text-lg text-indigo-600 break-all"><?php echo $your_metamask_address; ?></strong></p>
                    <p>3. After sending, enter the amount and the Transaction Hash (TxID) from your wallet activity below.</p>
                    <form action="deposit.php" method="POST" class="mt-6 space-y-4">
                        <input type="hidden" name="payment_method" value="MetaMask">
                        <div>
                            <label for="amount_metamask" class="block text-sm font-medium text-gray-700">Amount Sent ($)</label>
                            <input type="text" name="amount" id="amount_metamask" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" placeholder="e.g., 50.00" required>
                        </div>
                        <div>
                            <label for="transaction_id_metamask" class="block text-sm font-medium text-gray-700">Transaction Hash (TxID)</label>
                            <input type="text" name="transaction_id" id="transaction_id_metamask" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" placeholder="e.g., 0x123abc..." required>
                        </div>
                        <button type="submit" name="confirm_manual_deposit" class="w-full inline-flex justify-center py-3 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Submit Deposit for Review
                        </button>
                    </form>
                </div>

                <!-- bKash Content -->
                <div id="content-bkash" class="hidden tab-content space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800">bKash Deposit Instructions</h3>
                    <p>1. Go to your bKash App and choose 'Send Money'.</p>
                    <p>2. Enter the following number: <strong class="font-mono text-lg text-indigo-600"><?php echo $your_bkash_number; ?></strong></p>
                    <p>3. After sending, enter the amount and the Transaction ID (TrxID) from your bKash receipt below.</p>
                    <form action="deposit.php" method="POST" class="mt-6 space-y-4">
                        <input type="hidden" name="payment_method" value="bKash">
                        <div>
                            <label for="amount_bkash" class="block text-sm font-medium text-gray-700">Amount Sent (BDT)</label>
                            <input type="text" name="amount" id="amount_bkash" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" placeholder="e.g., 5000" required>
                        </div>
                        <div>
                            <label for="transaction_id_bkash" class="block text-sm font-medium text-gray-700">Transaction ID (TrxID)</label>
                            <input type="text" name="transaction_id" id="transaction_id_bkash" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" placeholder="Enter the ID from your receipt" required>
                        </div>
                        <button type="submit" name="confirm_manual_deposit" class="w-full inline-flex justify-center py-3 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Submit Deposit for Review
                        </button>
                    </form>
                </div>

                <!-- Nagad Content -->
                <div id="content-nagad" class="hidden tab-content space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800">Nagad Deposit Instructions</h3>
                    <p>1. Go to your Nagad App and choose 'Send Money'.</p>
                    <p>2. Enter the following number: <strong class="font-mono text-lg text-indigo-600"><?php echo $your_nagad_number; ?></strong></p>
                    <p>3. After sending, enter the amount and the Transaction ID from your Nagad receipt below.</p>
                     <form action="deposit.php" method="POST" class="mt-6 space-y-4">
                        <input type="hidden" name="payment_method" value="Nagad">
                        <div>
                            <label for="amount_nagad" class="block text-sm font-medium text-gray-700">Amount Sent (BDT)</label>
                            <input type="text" name="amount" id="amount_nagad" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" placeholder="e.g., 5000" required>
                        </div>
                        <div>
                            <label for="transaction_id_nagad" class="block text-sm font-medium text-gray-700">Transaction ID</label>
                            <input type="text" name="transaction_id" id="transaction_id_nagad" class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-3" placeholder="Enter the ID from your receipt" required>
                        </div>
                        <button type="submit" name="confirm_manual_deposit" class="w-full inline-flex justify-center py-3 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Submit Deposit for Review
                        </button>
                    </form>
                </div>
            </div>
             <?php if(!empty($error_message)): ?>
                <div class="mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- JAVASCRIPT for the dynamic form -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethodSelect = document.getElementById('payment_method');
        const instructionsContainer = document.getElementById('deposit-instructions');
        const contents = document.querySelectorAll('.tab-content');
        const formPaymentMethodInput = document.getElementById('form_payment_method');

        paymentMethodSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            
            // Hide all details first
            instructionsContainer.classList.add('hidden');
            contents.forEach(content => content.classList.add('hidden'));

            if (selectedValue) {
                instructionsContainer.classList.remove('hidden');
                // We find the correct content div and show it
                const contentToShow = document.getElementById('content-' + selectedValue);
                if(contentToShow) {
                    contentToShow.classList.remove('hidden');
                }
            }
        });
    });
</script>

</main>
</body>
</html>
