<?php

namespace App\Http\Controllers;

use App\Events\TransactionCreated;
use App\Models\Transaction;
use App\Repositories\ProductRepository;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ApiResponse;

    public function __construct(public ProductRepository $repo) { }

    public function createTransaction(array $transactionData): Transaction
    {
        $transaction = Transaction::create($transactionData);

        TransactionCreated::dispatch($transaction, $transactionData);

        return $transaction;
    }
}
