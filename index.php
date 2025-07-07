<?php
require_once 'header.php'; // Include our header

// --- DATA FETCHING ---
$events_stmt = $conn->query("SELECT * FROM bet_events WHERE status = 'open' AND closes_at > NOW() ORDER BY closes_at ASC");
$events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);
$event_ids = !empty($events) ? implode(',', array_column($events, 'event_id')) : '0';
$pools_stmt = $conn->query("SELECT event_id, SUM(stake_amount) as total_pool FROM user_bets WHERE event_id IN ($event_ids) GROUP BY event_id");
$all_pools = $pools_stmt->fetchAll(PDO::FETCH_ASSOC);
$pools_by_event = [];
foreach ($all_pools as $pool) {
    $pools_by_event[$pool['event_id']] = $pool['total_pool'];
}
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Live Events</h1>

    <?php if (empty($events)): ?>
        <div class="text-center py-16 px-4 bg-white rounded-lg shadow">
            <h2 class="text-xl font-medium text-gray-900">No Live Events Right Now</h2>
            <p class="text-gray-500 mt-2">Please check back later for new betting opportunities.</p>
        </div>
    <?php else: ?>
        <div class="bet-grid">
            <?php foreach ($events as $event): ?>
                <?php
                $total_pool = isset($pools_by_event[$event['event_id']]) ? $pools_by_event[$event['event_id']] : 0;
                ?>
                <div class="bet-card">
                    <div class="bet-card-header">
                        <!-- NEW: HTML structure for the beautiful countdown -->
                        <div class="countdown-container" data-closes-at="<?php echo htmlspecialchars($event['closes_at']); ?>">
                            <div class="countdown-block">
                                <span class="countdown-number" data-unit="days">00</span>
                                <span class="countdown-label">Days</span>
                            </div>
                            <span class="countdown-separator">:</span>
                            <div class="countdown-block">
                                <span class="countdown-number" data-unit="hours">00</span>
                                <span class="countdown-label">Hours</span>
                            </div>
                             <span class="countdown-separator">:</span>
                            <div class="countdown-block">
                                <span class="countdown-number" data-unit="minutes">00</span>
                                <span class="countdown-label">Mins</span>
                            </div>
                             <span class="countdown-separator">:</span>
                            <div class="countdown-block">
                                <span class="countdown-number" data-unit="seconds">00</span>
                                <span class="countdown-label">Secs</span>
                            </div>
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
    <?php endif; ?>
</div>

<!-- UPDATED JAVASCRIPT for the new countdown timer structure -->
<script>
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

                // Add a leading zero if the number is less than 10
                daysEl.textContent = String(days).padStart(2, '0');
                hoursEl.textContent = String(hours).padStart(2, '0');
                minutesEl.textContent = String(minutes).padStart(2, '0');
                secondsEl.textContent = String(seconds).padStart(2, '0');

            }, 1000);
        });
    }
    window.addEventListener('load', initializeCountdown);
</script>

</main>
</body>
</html>
