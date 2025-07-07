<?php
// This file does not need the header as it only returns HTML fragments

require_once 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['page']) || !is_numeric($_GET['page'])) {
    exit(); // Exit if parameters are missing
}

$category_id = intval($_GET['id']);
$page = intval($_GET['page']);
$events_per_page = 15;
$offset = ($page - 1) * $events_per_page;

// Fetch the next chunk of events
$events_stmt = $conn->prepare("SELECT * FROM bet_events WHERE status = 'open' AND closes_at > NOW() AND category_id = ? ORDER BY closes_at ASC LIMIT ? OFFSET ?");
$events_stmt->bindValue(1, $category_id, PDO::PARAM_INT);
$events_stmt->bindValue(2, $events_per_page, PDO::PARAM_INT);
$events_stmt->bindValue(3, $offset, PDO::PARAM_INT);
$events_stmt->execute();
$events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch their pool data
$event_ids = !empty($events) ? implode(',', array_column($events, 'event_id')) : '0';
$pools_stmt = $conn->query("SELECT event_id, SUM(stake_amount) as total_pool FROM user_bets WHERE event_id IN ($event_ids) GROUP BY event_id");
$all_pools = $pools_stmt->fetchAll(PDO::FETCH_ASSOC);
$pools_by_event = [];
foreach ($all_pools as $pool) {
    $pools_by_event[$pool['event_id']] = $pool['total_pool'];
}

// Loop through the results and echo the HTML for each card
foreach ($events as $event) {
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
<?php
}
?>
