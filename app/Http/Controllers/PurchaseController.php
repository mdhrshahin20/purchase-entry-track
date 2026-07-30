<?php

namespace App\Http\Controllers;

use App\Foundation\Request;
use App\Foundation\Response;
use App\Http\Requests\StorePurchaseRequest;
use App\Services\PurchaseService;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchases)
    {
    }

    public function index(Request $request): Response
    {
        return $this->view('purchase/form', $this->withUrls($request, [
            'title' => 'Purchase Entry',
        ]));
    }

    public function store(StorePurchaseRequest $request): Response
    {
        try {
            $id = $this->purchases->store($request->validated(), $request->ip());
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Unable to save purchase. Please try again.',
            ], 500);
        }

        $this->purchases->rememberSubmission();

        return $this->json([
            'success' => true,
            'message' => 'Purchase saved successfully.',
            'id' => $id,
        ]);
    }
}
