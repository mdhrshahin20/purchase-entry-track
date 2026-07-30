<?php

namespace App\Services;

use App\Models\Purchase;

/**
 * Application service — orchestrates purchase persistence and side effects.
 */
class PurchaseService
{
    public function __construct(private Purchase $purchases = new Purchase())
    {
    }

    /**
     * @param array<string, mixed> $input Validated form data
     */
    public function store(array $input, string $buyerIp): int
    {
        /** @var \App\Foundation\Application $app */
        $app = $GLOBALS['app'];

        return $this->purchases->create([
            'amount' => $input['amount'],
            'buyer' => $input['buyer'],
            'receipt_id' => $input['receipt_id'],
            'items' => $input['items'],
            'buyer_email' => $input['buyer_email'],
            'buyer_ip' => $buyerIp,
            'note' => $input['note'],
            'city' => $input['city'],
            'phone' => $input['phone'],
            'hash_key' => hash('sha512', $input['receipt_id'] . $app->config('app.hash_salt')),
            'entry_at' => date('Y-m-d'),
            'entry_by' => $input['entry_by'],
        ]);
    }

    public function rememberSubmission(): void
    {
        /** @var \App\Foundation\Application $app */
        $app = $GLOBALS['app'];

        setcookie(
            (string) $app->config('app.submit_cookie_name'),
            '1',
            [
                'expires' => time() + (int) $app->config('app.submit_cookie_ttl'),
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    /**
     * @param array{date_from?:string, date_to?:string, entry_by?:string} $filters
     * @return array{rows: list<array<string, mixed>>, total: int, page: int, total_pages: int}
     */
    public function paginateReport(array $filters, int $page, int $perPage): array
    {
        $total = $this->purchases->countFiltered($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        return [
            'rows' => $this->purchases->findFiltered($filters, $perPage, $offset),
            'total' => $total,
            'page' => $page,
            'total_pages' => $totalPages,
        ];
    }
}
