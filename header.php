<?php
// --- ROBUST SESSION MANAGEMENT ---
$session_path = __DIR__ . '/sessions';
if (!is_dir($session_path)) {
    mkdir($session_path, 0755, true);
}
ini_set('session.save_path', $session_path);
session_start();
// --- END OF SESSION MANAGEMENT ---

// Include the database connection
require_once 'db.php';

// Fetch categories from the database to build the menu
$category_order = "'Politics', 'Economics', 'Forex-Stock & Crypto Markets', 'Sports', 'Entertainment', 'Weather'";
$categories_stmt = $conn->query("SELECT category_id, name FROM bet_categories WHERE is_active = 1 ORDER BY FIELD(name, $category_order)");
$menu_categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bet Gamble - Predict and Win</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="homepage-body">
    <!-- 1. ADVERTISEMENT BANNER SPACE -->
    <div class="ad-banner-space bg-gray-200 text-center py-2 text-sm text-gray-500">
        Your Advertisement Banner Here (e.g., 728x90)
    </div>

    <!-- 2. MAIN HEADER -->
    <header class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <!-- Top Bar: Logo, Search, User Actions -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="index.php" class="flex items-center space-x-2">
                        <svg class="h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                        </svg>
                        <span class="text-3xl font-bold text-indigo-600">Bet Gamble</span>
                    </a>
                </div>

                <!-- Search Bar (Desktop) -->
                <div class="hidden md:flex flex-1 justify-center px-8">
                    <div class="relative w-full max-w-lg">
                        <input type="text" placeholder="Search events..." class="w-full bg-gray-100 border-gray-300 rounded-full py-3 pl-5 pr-12 focus:ring-indigo-500 focus:border-indigo-500">
                        <svg class="h-6 w-6 text-gray-400 absolute top-1/2 right-4 -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                </div>

                <!-- User Actions (Desktop) -->
                <div class="hidden md:flex items-center space-x-4">
                     <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="text-gray-600 hover:text-indigo-600 font-medium">Dashboard</a>
                        <a href="logout.php" class="ml-2 text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-md text-sm font-medium">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-600 hover:text-indigo-600 font-medium">Login</a>
                        <a href="register.php" class="ml-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                            Register
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Hamburger Button (Mobile) -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                         <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Bottom Bar: Category Menu (Desktop) -->
        <div class="hidden md:flex justify-center border-t border-gray-200">
            <div class="flex space-x-8 py-3">
                <!-- UPDATED FONT COLOR -->
                <a href="index.php" class="text-black hover:text-indigo-600 font-semibold">Home</a>
                <?php foreach ($menu_categories as $category): ?>
                    <!-- UPDATED FONT COLOR -->
                    <a href="category_page.php?id=<?php echo $category['category_id']; ?>" class="text-black hover:text-indigo-600 font-semibold">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Mobile Menu (Hidden by default) -->
        <div id="mobile-menu" class="md:hidden hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="index.php" class="text-gray-700 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium">Home</a>
                <?php foreach ($menu_categories as $category): ?>
                    <a href="category_page.php?id=<?php echo $category['category_id']; ?>" class="text-gray-700 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endforeach; ?>
                <hr class="my-2">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="text-gray-500 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium">Dashboard</a>
                    <a href="logout.php" class="text-gray-500 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-gray-500 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium">Login</a>
                    <a href="register.php" class="text-gray-500 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            menuButton.addEventListener('click', function () {
                mobileMenu.classList.toggle('hidden');
            });
        });
    </script>
