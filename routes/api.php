<?php

use App\Http\Controllers\API\V1\AirtimeController;
use App\Http\Controllers\API\V1\CashbackController;
use App\Http\Controllers\API\V1\DashboardController;
use App\Http\Controllers\API\V1\EarningAccountController;
use App\Http\Controllers\API\V1\EarningController;
use App\Http\Controllers\API\V1\FloatController;
use App\Http\Controllers\API\V1\PaymentsController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\SubscriptionController;
use App\Http\Controllers\API\V1\SubscriptionTypeController;
use App\Http\Controllers\API\V1\TransactionController;
use App\Http\Controllers\API\V1\UtilityController;
use App\Http\Controllers\API\V1\VoucherController;
use App\Http\Controllers\API\V1\WithdrawController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth.jwt')->prefix('/v1')->name('api.')->group(function() {
    Route::prefix('/products')->group(function() {
        Route::post('/airtime', AirtimeController::class);
        Route::post('/airtime/bulk', [AirtimeController::class, 'bulk']);
        Route::post('/utility', UtilityController::class);
        Route::post('/subscription', SubscriptionController::class);
        Route::post('/withdraw', WithdrawController::class);

        Route::get('/earnings/rates', [ProductController::class, 'getEarningRates']);

//        TODO: Should we have a similar endpoint for voucher purchase?
//          Route::post('/voucher', WithdrawController::class);

        Route::prefix('/vouchers')->group(function() {
            Route::post('/top-up', [VoucherController::class, 'topUp']);
            Route::post('/disburse', [VoucherController::class, 'disburse']);
        });

        Route::prefix('/subscriptions')->group(function() {
            Route::post('', SubscriptionController::class);
        });

        Route::post('/float/top-up', [FloatController::class, 'topUp']);

        //  AT Callback Route
        Route::post('/airtime/status/callback', [AirtimeController::class, 'airtimeStatusCallback']);

        Route::get('/subscription-types/default', SubscriptionTypeController::class);
    });

    Route::prefix('/payments')->group(function() {
        // Payments service callback
        Route::post('/callback', [PaymentsController::class, 'processCallback']);
    });

    Route::prefix('/accounts')->group(function() {
        Route::get('/airtime-accounts', [ProductController::class, 'getAllAirtimeAccounts']);
        Route::get('/utility-accounts', [ProductController::class, 'getAllUtilityAccounts']);

        Route::prefix('/{accountId}')->group(function() {
            Route::get('/details', [ProductController::class, 'getAccountDetails']);

            Route::get('/airtime-accounts', [ProductController::class, 'airtimeAccounts']);
            Route::get('/utility-accounts', [ProductController::class, 'utilityAccounts']);

            Route::get('/current-subscription', [ProductController::class, 'currentSubscription']);

            Route::get('/earnings', [ProductController::class, 'earnings']);
        });
    });

    Route::prefix('/savings')->group(function() {
        Route::post('/callback', [EarningController::class, 'processSavingsCallback']);
    });

    //  DASHBOARD ROUTES
    Route::get('/dashboard', [DashboardController::class, "index"]);
    Route::get('/dashboard/revenue-chart', [DashboardController::class, "revenueChart"]);

    Route::get('/transactions', [TransactionController::class, "index"]);
    Route::get('/transactions/{transaction}', [TransactionController::class, "show"]);
    Route::post('/transactions/{transaction}/check-payment', [TransactionController::class, "checkPayment"]);
    Route::post('/transactions/{transaction}/check-request', [TransactionController::class, "checkRequest"]);
    Route::post('/transactions/{transaction}/refund', [TransactionController::class, "refund"]);
    Route::post('/transactions/{transaction}/retry', [TransactionController::class, "retry"]);

    Route::get('/earning-accounts', [EarningAccountController::class, "index"]);
    Route::get('/earning-accounts/{earningAccount}', [EarningAccountController::class, "show"]);

    Route::get('/cashbacks', [CashbackController::class, "index"]);
    Route::get('/cashbacks/{cashback}', [CashbackController::class, "show"]);

    Route::get('/subscription-types', [SubscriptionController::class, "getSubTypes"]);

    Route::get('/subscriptions', [SubscriptionController::class, "index"]);
    Route::get('/subscriptions/{subscription}', [SubscriptionController::class, "show"]);
});

// TODO: Research on how to secure or throttle unsecured callback endpoints
Route::prefix('/v1')->name('api.')->group(function() {
    Route::prefix('/products')->group(function() {
        Route::prefix('/subscriptions')->group(function() {
            Route::post('/check-expiry', [SubscriptionController::class, 'checkExpiry']);
        });

        Route::prefix('/earnings')->group(function() {
            Route::post('/save', [EarningController::class, 'saveEarnings']);
        });
    });
});
