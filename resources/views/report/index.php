<?php
/** @var string $baseUrl */
/** @var string $appUrl */
/** @var array $filters */
/** @var array $rows */
/** @var string|null $error */
/** @var array{page:int,per_page:int,total:int,total_pages:int,from:int,to:int} $pagination */

$appUrl = $appUrl ?? ($baseUrl . '/index.php');

/**
 * Build a report URL preserving active filters (and optional page).
 */
$reportUrl = static function (array $filters, ?int $page = null) use ($appUrl): string {
    $query = array_filter([
        'date_from' => $filters['date_from'] ?? '',
        'date_to' => $filters['date_to'] ?? '',
        'entry_by' => $filters['entry_by'] ?? '',
        'page' => ($page !== null && $page > 1) ? (string) $page : '',
    ], static fn ($value) => $value !== '' && $value !== null);

    $path = $appUrl . '/report';
    return $query === [] ? $path : $path . '?' . http_build_query($query);
};
?>
<section class="page-head">
    <h1>Purchase Report</h1>
    <p>Filter submissions by date range and/or user ID.</p>
</section>

<form id="report-filter" class="filter-bar" method="get" action="<?= htmlspecialchars($appUrl) ?>/report">
    <div class="field">
        <label for="date_from">From</label>
        <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>">
    </div>
    <div class="field">
        <label for="date_to">To</label>
        <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>">
    </div>
    <div class="field">
        <label for="filter_entry_by">User ID</label>
        <input type="text" id="filter_entry_by" name="entry_by" inputmode="numeric" value="<?= htmlspecialchars($filters['entry_by']) ?>" placeholder="Any">
    </div>
    <div class="filter-actions">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a class="btn btn-ghost" href="<?= htmlspecialchars($appUrl) ?>/report">Clear</a>
    </div>
</form>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Amount</th>
                <th>Buyer</th>
                <th>Receipt</th>
                <th>Items</th>
                <th>Email</th>
                <th>City</th>
                <th>Phone</th>
                <th>Note</th>
                <th>Entry At</th>
                <th>Entry By</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="12" class="empty">No submissions found for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= (int) $row['id'] ?></td>
                        <td><?= (int) $row['amount'] ?></td>
                        <td><?= htmlspecialchars($row['buyer']) ?></td>
                        <td><?= htmlspecialchars($row['receipt_id']) ?></td>
                        <td><?= htmlspecialchars($row['items']) ?></td>
                        <td><?= htmlspecialchars($row['buyer_email']) ?></td>
                        <td><?= htmlspecialchars($row['city']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td class="note-cell" title="<?= htmlspecialchars($row['note']) ?>"><?= htmlspecialchars($row['note']) ?></td>
                        <td><?= htmlspecialchars($row['entry_at']) ?></td>
                        <td><?= (int) $row['entry_by'] ?></td>
                        <td><?= htmlspecialchars($row['buyer_ip']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination-bar">
    <p class="result-meta">
        <?php if ($pagination['total'] === 0): ?>
            0 record(s) shown.
        <?php else: ?>
            Showing <?= (int) $pagination['from'] ?>–<?= (int) $pagination['to'] ?>
            of <?= (int) $pagination['total'] ?> record(s)
            (page <?= (int) $pagination['page'] ?> of <?= (int) $pagination['total_pages'] ?>).
        <?php endif; ?>
    </p>

    <?php if ($pagination['total_pages'] > 1): ?>
        <nav class="pagination" aria-label="Report pagination">
            <?php if ($pagination['page'] > 1): ?>
                <a class="btn btn-ghost" href="<?= htmlspecialchars($reportUrl($filters, $pagination['page'] - 1)) ?>">Previous</a>
            <?php else: ?>
                <span class="btn btn-ghost is-disabled">Previous</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <?php if ($i === $pagination['page']): ?>
                    <span class="page-link is-active" aria-current="page"><?= $i ?></span>
                <?php else: ?>
                    <a class="page-link" href="<?= htmlspecialchars($reportUrl($filters, $i)) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                <a class="btn btn-ghost" href="<?= htmlspecialchars($reportUrl($filters, $pagination['page'] + 1)) ?>">Next</a>
            <?php else: ?>
                <span class="btn btn-ghost is-disabled">Next</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
