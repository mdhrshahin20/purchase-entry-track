<?php

namespace App\Http\Controllers;

use App\Foundation\Request;
use App\Foundation\Response;
use App\Services\PurchaseService;

class ReportController extends Controller
{
    private const PER_PAGE = 5;

    public function __construct(private PurchaseService $purchases)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->sanitizeFilters($request);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = self::PER_PAGE;

        $rows = [];
        $total = 0;
        $totalPages = 1;
        $error = null;

        try {
            $result = $this->purchases->paginateReport($filters, $page, $perPage);
            $rows = $result['rows'];
            $total = $result['total'];
            $totalPages = $result['total_pages'];
            $page = $result['page'];
        } catch (\Throwable $e) {
            $error = 'Unable to load report data. Check the database connection.';
        }

        $from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
        $to = min($page * $perPage, $total);

        return $this->view('report/index', $this->withUrls($request, [
            'title' => 'Purchase Report',
            'filters' => $filters,
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
        ]));
    }

    /** @return array{date_from:string, date_to:string, entry_by:string} */
    private function sanitizeFilters(Request $request): array
    {
        $filters = [
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'entry_by' => trim((string) $request->query('entry_by', '')),
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

        return $filters;
    }
}
