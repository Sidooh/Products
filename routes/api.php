<?php

use App\Http\Controllers\API\V1\AccountController;
use App\Http\Controllers\API\V1\AirtimeController;
use App\Http\Controllers\API\V1\CashbackController;
use App\Http\Controllers\API\V1\DashboardController;
use App\Http\Controllers\API\V1\EarningAccountController;
use App\Http\Controllers\API\V1\MerchantController;
use App\Http\Controllers\API\V1\PaymentsController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\SavingsController;
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

// TODO: Research on how to secure or throttle unsecured callback endpoints
Route::prefix('/v1')->group(function() {
    Route::prefix('/subscriptions')->group(function() {
        Route::post('/check-expiry', [SubscriptionController::class, 'checkExpiry']);
    });

    Route::prefix('/cashbacks')->group(function() {
        Route::post('/invest', [CashbackController::class, 'invest']);
    });

    Route::get('/providers/check-balances', [ProductController::class, 'queryProviderBalances']);
});

Route::prefix('/sidooh')->group(function() {
    // Payments service callback
    Route::post('/payments/callback', [PaymentsController::class, 'processCallback']);

    // Savings service callback
    Route::post('/savings/callback', [SavingsController::class, 'processCallback']);
});

//  AT Callback Route
Route::post('/airtime/status/callback', [AirtimeController::class, 'airtimeStatusCallback']);

//=========================================================================================================
// V1 API
//=========================================================================================================

Route::middleware('auth.jwt')->prefix('/v1')->name('api.')->group(function() {
    Route::prefix('/products')->group(function() {
        Route::post('/airtime', AirtimeController::class);
        Route::post('/utility', UtilityController::class);
        Route::post('/withdraw', WithdrawController::class);
        Route::post('/merchant', MerchantController::class);
        Route::post('/voucher', VoucherController::class);
        Route::post('/subscription', SubscriptionController::class);
    });

    Route::prefix('/transactions')->group(function() {
        Route::get('/', [TransactionController::class, 'index']);

        Route::prefix('/{transaction}')->group(function() {
            Route::get('/', [TransactionController::class, 'show']);

            Route::post('/check-payment', [TransactionController::class, 'checkPayment']);
            Route::post('/check-request', [TransactionController::class, 'checkRequest']);
            Route::post('/refund', [TransactionController::class, 'refund']);
            Route::post('/retry', [TransactionController::class, 'retry']);
            Route::post('/complete', [TransactionController::class, 'complete']);
            Route::post('/fail', [TransactionController::class, 'fail']);
        });
    });

    Route::prefix('/subscription-types')->group(function() {
        Route::get('/', [SubscriptionTypeController::class, 'index']);
        Route::get('/default', [SubscriptionTypeController::class, 'defaultSubscriptionType']);
    });

    Route::prefix('/subscriptions')->group(function() {
        Route::get('/{subscription}', [SubscriptionController::class, 'show']);
        Route::get('/', [SubscriptionController::class, 'index']);
    });

    Route::prefix('/earning-accounts')->group(function() {
        Route::get('/', [EarningAccountController::class, 'index']);
        Route::get('/{earning_account}', [EarningAccountController::class, 'show']);
    });

    Route::prefix('/cashbacks')->group(function() {
        Route::get('/', [CashbackController::class, 'index']);
        Route::get('/{cashback}', [CashbackController::class, 'show']);
    });

    Route::get('/airtime/accounts', [AirtimeController::class, 'accounts']);
    Route::get('/utility/accounts', [UtilityController::class, 'accounts']);

    Route::prefix('/accounts')->group(function() {
        Route::prefix('/{accountId}')->group(function() {
            Route::get('/details', [AccountController::class, 'show']);

            Route::get('/airtime-accounts', [AccountController::class, 'airtimeAccounts']);
            Route::get('/utility-accounts', [AccountController::class, 'utilityAccounts']);

            Route::get('/current-subscription', [AccountController::class, 'currentSubscription']);

            Route::get('/earnings', [AccountController::class, 'earnings']);
        });
    });

    //  DASHBOARD ROUTES
    Route::prefix('/dashboard')->group(function() {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/revenue-chart', [DashboardController::class, 'revenueChart']);
    });

    // Utilities
    Route::get('/earnings/rates', [ProductController::class, 'getEarningRates']);
});
