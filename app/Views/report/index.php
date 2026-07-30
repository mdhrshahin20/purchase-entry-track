<?php

use App\Core\Url;

/** @var string $baseUrl */
/** @var array $filters */
/** @var list<int> $perPageOptions */
/** @var array $rows */
/** @var string|null $error */
/** @var array{page:int,per_page:int,total:int,total_pages:int,from:int,to:int} $pagination */

$reportPath = Url::path('/report');
$perPageOptions = $perPageOptions ?? [5, 10, 25, 50];
$currentPerPage = (int) ($pagination['per_page'] ?? 5);

/**
 * Build a report URL preserving filters, per_page, and optional page.
 */
$reportUrl = static function (array $filters, int $perPage, ?int $page = null) use ($reportPath): string {
    $query = array_filter([
        'date_from' => $filters['date_from'] ?? '',
        'date_to' => $filters['date_to'] ?? '',
        'entry_by' => $filters['entry_by'] ?? '',
        'per_page' => $perPage !== 5 ? (string) $perPage : '',
        'page' => ($page !== null && $page > 1) ? (string) $page : '',
    ], static function ($value) {
        return $value !== '' && $value !== null;
    });

    return $query === [] ? $reportPath : $reportPath . '?' . http_build_query($query);
};
?>
<section class="page-head">
    <h1>Purchase Report</h1>
    <p>Browse submissions with filters. Primary fields are listed first; open a row for contact details and note.</p>
</section>

<form id="report-filter" class="filter-bar" method="get" action="<?= htmlspecialchars($reportPath) ?>">
    <?php if ($currentPerPage !== 5): ?>
        <input type="hidden" name="per_page" value="<?= (int) $currentPerPage ?>">
    <?php endif; ?>
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
        <a class="btn btn-ghost" href="<?= htmlspecialchars($reportPath) ?>">Clear</a>
    </div>
</form>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="table-wrap">
    <table class="data-table report-table">
        <thead>
            <tr>
                <th class="col-serial">#</th>
                <th>Date</th>
                <th>Buyer</th>
                <th>Amount</th>
                <th>Receipt</th>
                <th>Items</th>
                <th>City</th>
                <th>Entry By</th>
                <th class="col-toggle">Details</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="9" class="empty">No submissions found for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <?php
                    $detailId = 'detail-' . (int) $row['id'];
                    $serialNo = (int) ($pagination['from'] ?? 1) + (int) $index;
                    ?>
                    <tr class="report-row">
                        <td class="col-serial"><?= $serialNo ?></td>
                        <td><?= htmlspecialchars($row['entry_at']) ?></td>
                        <td><?= htmlspecialchars($row['buyer']) ?></td>
                        <td><?= (int) $row['amount'] ?></td>
                        <td><?= htmlspecialchars($row['receipt_id']) ?></td>
                        <td class="cell-clamp" title="<?= htmlspecialchars($row['items']) ?>"><?= htmlspecialchars($row['items']) ?></td>
                        <td><?= htmlspecialchars($row['city']) ?></td>
                        <td><?= (int) $row['entry_by'] ?></td>
                        <td class="col-toggle">
                            <button
                                type="button"
                                class="btn-detail"
                                aria-expanded="false"
                                aria-controls="<?= htmlspecialchars($detailId) ?>"
                                data-detail-toggle="<?= htmlspecialchars($detailId) ?>"
                                title="Show more details"
                            >
                                <span class="btn-detail-chevron" aria-hidden="true"></span>
                                <span class="btn-detail-label">Details</span>
                            </button>
                        </td>
                    </tr>
                    <tr id="<?= htmlspecialchars($detailId) ?>" class="report-detail" hidden>
                        <td colspan="9">
                            <div class="detail-panel">
                                <div class="detail-panel-head">
                                    <strong>Extra details</strong>
                                    <button
                                        type="button"
                                        class="btn-detail-close"
                                        data-detail-close="<?= htmlspecialchars($detailId) ?>"
                                    >
                                        Close
                                    </button>
                                </div>
                                <div class="detail-grid">
                                    <div class="detail-card">
                                        <span class="detail-label">ID</span>
                                        <span class="detail-value"><?= (int) $row['id'] ?></span>
                                    </div>
                                    <div class="detail-card">
                                        <span class="detail-label">Email</span>
                                        <span class="detail-value"><?= htmlspecialchars($row['buyer_email']) ?></span>
                                    </div>
                                    <div class="detail-card">
                                        <span class="detail-label">Phone</span>
                                        <span class="detail-value"><?= htmlspecialchars($row['phone']) ?></span>
                                    </div>
                                    <div class="detail-card">
                                        <span class="detail-label">Buyer IP</span>
                                        <span class="detail-value"><?= htmlspecialchars($row['buyer_ip']) ?></span>
                                    </div>
                                    <div class="detail-card detail-note">
                                        <span class="detail-label">Note</span>
                                        <span class="detail-value"><?= htmlspecialchars($row['note']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination-bar">
    <form class="per-page-form" method="get" action="<?= htmlspecialchars($reportPath) ?>">
        <?php if (($filters['date_from'] ?? '') !== ''): ?>
            <input type="hidden" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>">
        <?php endif; ?>
        <?php if (($filters['date_to'] ?? '') !== ''): ?>
            <input type="hidden" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>">
        <?php endif; ?>
        <?php if (($filters['entry_by'] ?? '') !== ''): ?>
            <input type="hidden" name="entry_by" value="<?= htmlspecialchars($filters['entry_by']) ?>">
        <?php endif; ?>

        <label for="per_page">Per page</label>
        <select id="per_page" name="per_page" onchange="this.form.submit()">
            <?php foreach ($perPageOptions as $option): ?>
                <option value="<?= (int) $option ?>" <?= $currentPerPage === (int) $option ? 'selected' : '' ?>>
                    <?= (int) $option ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

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
                <a class="btn btn-ghost" href="<?= htmlspecialchars($reportUrl($filters, $currentPerPage, $pagination['page'] - 1)) ?>">Previous</a>
            <?php else: ?>
                <span class="btn btn-ghost is-disabled">Previous</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <?php if ($i === $pagination['page']): ?>
                    <span class="page-link is-active" aria-current="page"><?= $i ?></span>
                <?php else: ?>
                    <a class="page-link" href="<?= htmlspecialchars($reportUrl($filters, $currentPerPage, $i)) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                <a class="btn btn-ghost" href="<?= htmlspecialchars($reportUrl($filters, $currentPerPage, $pagination['page'] + 1)) ?>">Next</a>
            <?php else: ?>
                <span class="btn btn-ghost is-disabled">Next</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>

<script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/report.js"></script>
