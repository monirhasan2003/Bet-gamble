<?php
require_once 'header.php'; // Handles session, DB connection, and nav bar

// --- SECURITY AND DATA FETCHING ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: Invalid Category ID.");
}
$category_id = intval($_GET['id']);

$category_name_stmt = $conn->prepare("SELECT name FROM bet_categories WHERE category_id = ?");
$category_name_stmt->execute([$category_id]);
$category_name = $category_name_stmt->fetchColumn();

if (!$category_name) {
    die("Error: Category not found.");
}

// --- UPDATED: Fetch only the first 15 events ---
$events_per_page = 15;
$events_stmt = $conn->prepare("SELECT * FROM bet_events WHERE status = 'open' AND closes_at > NOW() AND category_id = ? ORDER BY closes_at ASC LIMIT ?");
$events_stmt->bindValue(1, $category_id, PDO::PARAM_INT);
$events_stmt->bindValue(2, $events_per_page, PDO::PARAM_INT);
$events_stmt->execute();
$events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Count total events to know if we need a 'Load More' button ---
$total_events_stmt = $conn->prepare("SELECT COUNT(*) FROM bet_events WHERE status = 'open' AND closes_at > NOW() AND category_id = ?");
$total_events_stmt->execute([$category_id]);
$total_events = $total_events_stmt->fetchColumn();

// --- (The rest of the data fetching is the same) ---
$event_ids = !empty($events) ? implode(',', array_column($events, 'event_id')) : '0';
$pools_stmt = $conn->query("SELECT event_id, SUM(stake_amount) as total_pool FROM user_bets WHERE event_id IN ($event_ids) GROUP BY event_id");
$all_pools = $pools_stmt->fetchAll(PDO::FETCH_ASSOC);
$pools_by_event = [];
foreach ($all_pools as $pool) {
    $pools_by_event[$pool['event_id']] = $pool['total_pool'];
}
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">
        <?php echo htmlspecialchars($category_name); ?> Events
    </h1>

    <?php if (empty($events)): ?>
        <div class="text-center py-16 px-4 bg-white rounded-lg shadow">
            <h2 class="text-xl font-medium text-gray-900">No Live Events in this Category</h2>
            <p class="text-gray-500 mt-2">Please check back later or view other categories.</p>
        </div>
    <?php else: ?>
        <!-- The container for our event cards -->
        <div id="events-grid-container" class="bet-grid">
            <?php foreach ($events as $event): ?>
                <?php
                $total_pool = isset($pools_by_event[$event['event_id']]) ? $pools_by_event[$event['event_id']] : 0;
                ?>
                <div class="bet-card">
                    <div class="bet-card-header">
                        <div class="countdown-container" data-closes-at="<?php echo htmlspecialchars($event['closes_at']); ?>">
                            <div class="countdown-block"><span class="countdown-number" data-unit="days">00</span><span class="countdown-label">Days</span></div>
                            <span class="countdown-separator">:</span>
                            <div class="countdown-block"><span class="countdown-number" data-unit="hours">00</span><span class="countdown-label">Hours</span></div>
                            <span class="countdown-separator">:</span>
                            <div class="countdown-block"><span class="countdown-number" data-unit="minutes">00</span><span class="countdown-label">Mins</span></div>
                            <span class="countdown-separator">:</span>
                            <div class="countdown-block"><span class="countdown-number" data-unit="seconds">00</span><span class="countdown-label">Secs</span></div>
                        </div>
                    </div>
                    <div class="bet-card-body">
                        <p class="bet-card-question"><?php echo htmlspecialchars($event['question']); ?></p>
                        <div class="mt-4 text-center">
                             <a href="view_event.php?event_id=<?php echo $event['event_id']; ?>" class="w-full text-center block text-white font-bold py-2 px-4 rounded-md bg-indigo-600 hover:bg-indigo-700">
                                View Details & Bet
                            </a>
                        </div>
                    </div>
                    <div class="bet-card-footer">
                        Total Pool: $<?php echo number_format($total_pool, 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- NEW: Load More Button -->
        <div id="load-more-container" class="text-center mt-8">
            <?php if ($total_events > $events_per_page): ?>
                <button id="load-more-btn" class="bg-indigo-600 text-white font-semibold py-3 px-8 rounded-lg shadow-md hover:bg-indigo-700 transition">
                    Load More
                </button>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<!-- JAVASCRIPT for Countdown and NEW Load More functionality -->
<script>
    // --- Countdown Timer Logic (Same as before) ---
    function initializeCountdown() {
        const timerContainers = document.querySelectorAll('.countdown-container');
        timerContainers.forEach(container => {
            const closesAt = new Date(container.dataset.closesAt.replace(' ', 'T')).getTime();
            const daysEl = container.querySelector('[data-unit="days"]');
            const hoursEl = container.querySelector('[data-unit="hours"]');
            const minutesEl = container.querySelector('[data-unit="minutes"]');
            const secondsEl = container.querySelector('[data-unit="seconds"]');
            const interval = setInterval(() => {
                const now = new Date().getTime();
                const distance = closesAt - now;
                if (distance < 0) {
                    clearInterval(interval);
                    container.innerHTML = "<span class='text-red-600 font-semibold'>Betting Closed</span>";
                    return;
                }
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                if(daysEl) daysEl.textContent = String(days).padStart(2, '0');
                if(hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
                if(minutesEl) minutesEl.textContent = String(minutes).padStart(2, '0');
                if(secondsEl) secondsEl.textContent = String(seconds).padStart(2, '0');
            }, 1000);
        });
    }
    
    // --- NEW: Load More Logic ---
    let currentPage = 1;
    const loadMoreBtn = document.getElementById('load-more-btn');
    const eventsGridContainer = document.getElementById('events-grid-container');
    const categoryId = <?php echo $category_id; ?>;

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            currentPage++;
            loadMoreBtn.textContent = 'Loading...';
            loadMoreBtn.disabled = true;

            // Fetch more events from a helper file
            fetch(`load_more_events.php?id=${categoryId}&page=${currentPage}`)
                .then(response => response.text())
                .then(html => {
                    if (html.trim() !== "") {
                        eventsGridContainer.insertAdjacentHTML('beforeend', html);
                        initializeCountdown(); // Re-initialize countdown for new elements
                        loadMoreBtn.textContent = 'Load More';
                        loadMoreBtn.disabled = false;
                    } else {
                        // No more events to load
                        loadMoreBtn.textContent = 'No More Events';
                        loadMoreBtn.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error loading more events:', error);
                    loadMoreBtn.textContent = 'Error';
                });
        });
    }

    // Initialize countdown timers when the page loads
    window.addEventListener('load', initializeCountdown);
</script>

</main>
</body>
</html>
