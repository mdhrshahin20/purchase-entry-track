<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Purchase;

/**
 * Purchase report listing with filters and pagination.
 */
class ReportController extends Controller
{
    /**
     * Default number of rows when per_page is omitted.
     */
    private const DEFAULT_PER_PAGE = 5;

    /**
     * Allowed per-page sizes selectable by the user.
     *
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [5, 10, 25, 50];

    /**
     * Render the filtered, paginated report page.
     *
     * @return void
     */
    public function index(): void
    {
        $filters = [
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
            'entry_by' => trim((string) ($_GET['entry_by'] ?? '')),
        ];

        if ($filters['date_from'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from'])) {
            $filters['date_from'] = '';
        }
        if ($filters['date_to'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to'])) {
            $filters['date_to'] = '';
        }
        if ($filters['entry_by'] !== '' && !preg_match('/^\d+$/', $filters['entry_by'])) {
            $filters['entry_by'] = '';
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) ($_GET['per_page'] ?? self::DEFAULT_PER_PAGE);
        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $rows = [];
        $total = 0;
        $totalPages = 1;
        $error = null;

        try {
            $purchase = new Purchase();
            $total = $purchase->countFiltered($filters);
            $totalPages = max(1, (int) ceil($total / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $offset = ($page - 1) * $perPage;
            $rows = $purchase->findFiltered($filters, $perPage, $offset);
        } catch (\Throwable $e) {
            $error = 'Unable to load report data. Check the database connection.';
        }

        $from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
        $to = min($page * $perPage, $total);

        $this->view('report/index', [
            'title' => 'Purchase Report',
            'baseUrl' => $this->baseUrl(),
            'appUrl' => $this->appUrl(),
            'homeUrl' => $this->homeUrl(),
            'csrfToken' => Csrf::token(),
            'filters' => $filters,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'rows' => $rows,
            'error' => $error,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
