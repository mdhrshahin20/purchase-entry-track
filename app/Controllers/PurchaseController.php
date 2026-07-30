<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\SubmitLock;
use App\Core\Validator;
use App\Models\Purchase;

/**
 * Purchase entry form and AJAX store endpoint.
 */
class PurchaseController extends Controller
{
    /**
     * Display the purchase entry form.
     *
     * @return void
     */
    public function index(): void
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $submitLock = SubmitLock::status($config);

        $this->view('purchase/form', [
            'title' => 'Purchase Entry',
            'baseUrl' => $this->baseUrl(),
            'appUrl' => $this->appUrl(),
            'csrfToken' => Csrf::token(),
            'submitLock' => $submitLock,
        ]);
    }

    /**
     * Validate and persist a purchase via AJAX (JSON response).
     *
     * Enforces CSRF, the 24-hour cookie lock, backend validation, and
     * server-only buyer_ip / hash_key / entry_at values.
     *
     * @return void
     */
    public function store(): void
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';

        if (!Csrf::validate(Csrf::tokenFromRequest())) {
            $this->json([
                'success' => false,
                'message' => 'CSRF token mismatch. Please refresh the page and try again.',
            ], 419);
            return;
        }

        $lock = SubmitLock::status($config);
        if ($lock !== null) {
            $this->json([
                'success' => false,
                'message' => $lock['message'],
                'submit_lock' => $lock,
            ], 429);
            return;
        }

        $input = [
            'amount' => $_POST['amount'] ?? '',
            'buyer' => trim((string) ($_POST['buyer'] ?? '')),
            'receipt_id' => trim((string) ($_POST['receipt_id'] ?? '')),
            'items' => trim((string) ($_POST['items'] ?? '')),
            'buyer_email' => trim((string) ($_POST['buyer_email'] ?? '')),
            'note' => trim((string) ($_POST['note'] ?? '')),
            'city' => trim((string) ($_POST['city'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'entry_by' => trim((string) ($_POST['entry_by'] ?? '')),
        ];

        $validator = new Validator();
        if (!$validator->validate($input)) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
            return;
        }

        $buyerIp = substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 20);
        $hashKey = hash('sha512', $input['receipt_id'] . $config['hash_salt']);
        $entryAt = date('Y-m-d');

        try {
            $purchase = new Purchase();
            $id = $purchase->create([
                'amount' => $input['amount'],
                'buyer' => $input['buyer'],
                'receipt_id' => $input['receipt_id'],
                'items' => $input['items'],
                'buyer_email' => $input['buyer_email'],
                'buyer_ip' => $buyerIp,
                'note' => $input['note'],
                'city' => $input['city'],
                'phone' => $input['phone'],
                'hash_key' => $hashKey,
                'entry_at' => $entryAt,
                'entry_by' => $input['entry_by'],
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Unable to save purchase. Please try again.',
            ], 500);
            return;
        }

        $unlock = SubmitLock::setCookie($config);

        $this->json([
            'success' => true,
            'message' => sprintf(
                'Purchase saved successfully. Next submission after %s (%s left).',
                $unlock['available_at_label'],
                $unlock['remaining_label']
            ),
            'id' => $id,
            'submit_lock' => [
                'locked' => true,
                'available_at' => $unlock['available_at'],
                'available_at_label' => $unlock['available_at_label'],
                'remaining_seconds' => $unlock['remaining_seconds'],
                'remaining_label' => $unlock['remaining_label'],
                'message' => sprintf(
                    'Next submission available after %s (%s left).',
                    $unlock['available_at_label'],
                    $unlock['remaining_label']
                ),
            ],
        ]);
    }
}
