<?php
/**
 * Renders the event list from auction_event_timeline() as a vertical timeline,
 * shared by the user's auction detail page and the admin one.
 */
function render_auction_timeline(array $events): void
{
    if (!$events) {
        echo '<p class="admin-empty">Nothing has happened with this auction yet.</p>';
        return;
    }
    ?>
    <ol class="timeline">
        <?php foreach ($events as $event): ?>
            <li class="timeline-item timeline-<?= htmlspecialchars($event['tone']) ?>">
                <time class="timeline-time"><?= htmlspecialchars($event['time'] ?? 'time unknown') ?></time>
                <div class="timeline-body">
                    <span class="timeline-label"><?= htmlspecialchars($event['label']) ?></span>
                    <?php if ($event['meta'] !== ''): ?>
                        <span class="timeline-meta"><?= htmlspecialchars($event['meta']) ?></span>
                    <?php endif; ?>
                    <?php if ($event['detail'] !== ''): ?>
                        <p class="timeline-detail"><?= htmlspecialchars($event['detail']) ?></p>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
    <?php
}
