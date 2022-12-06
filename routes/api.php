<?php

use App\Http\Controllers\API\V1\AccountController as AccountControllerV2;
use App\Http\Controllers\API\V1\AirtimeController;
use App\Http\Controllers\API\V1\AirtimeController as AirtimeControllerV2;
use App\Http\Controllers\API\V1\CashbackController as CashbackControllerV2;
use App\Http\Controllers\API\V1\DashboardController;
use App\Http\Controllers\API\V1\EarningAccountController as EarningAccountControllerV2;
use App\Http\Controllers\API\V1\MerchantController as MerchantControllerV2;
use App\Http\Controllers\API\V1\PaymentsController as PaymentsControllerV2;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\SavingsController;
use App\Http\Controllers\API\V1\SubscriptionController as SubscriptionControllerV2;
use App\Http\Controllers\API\V1\SubscriptionTypeController as SubscriptionTypeControllerV2;
use App\Http\Controllers\API\V1\TransactionController as TransactionControllerV2;
use App\Http\Controllers\API\V1\UtilityController as UtilityControllerV2;
use App\Http\Controllers\API\V1\VoucherController as VoucherControllerV2;
use App\Http\Controllers\API\V1\WithdrawController as WithdrawControllerV2;
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
Route::prefix('/v1')->group(function () {
    Route::prefix('/subscriptions')->group(function () {
        Route::post('/check-expiry', [SubscriptionControllerV2::class, 'checkExpiry']);
    });

    Route::prefix('/cashbacks')->group(function () {
        Route::post('/invest', [CashbackControllerV2::class, 'invest']);
    });

});

Route::prefix('/sidooh')->group(function () {
    // Payments service callback
    Route::post('/payments/callback', [PaymentsControllerV2::class, 'processCallback']);

    // Savings service callback
    Route::post('/savings/callback', [SavingsController::class, 'processCallback']);
});

//  AT Callback Route
Route::post('/airtime/status/callback', [AirtimeController::class, 'airtimeStatusCallback']);

#=========================================================================================================
# V1 API
#=========================================================================================================

Route::middleware('auth.jwt')->prefix('/v1')->name('api.')->group(function () {
    Route::prefix('/products')->group(function () {
        Route::post('/airtime', AirtimeControllerV2::class);
        Route::post('/utility', UtilityControllerV2::class);
        Route::post('/withdraw', WithdrawControllerV2::class);
        Route::post('/merchant', MerchantControllerV2::class);
        Route::post('/voucher', VoucherControllerV2::class);
        Route::post('/subscription', SubscriptionControllerV2::class);
    });

    Route::prefix('/transactions')->group(function () {
        Route::get('/', [TransactionControllerV2::class, 'index']);

        Route::prefix('/{transaction}')->group(function () {
            Route::get('/', [TransactionControllerV2::class, 'show']);

            Route::post('/check-payment', [TransactionControllerV2::class, 'checkPayment']);
            Route::post('/check-request', [TransactionControllerV2::class, 'checkRequest']);
            Route::post('/refund', [TransactionControllerV2::class, 'refund']);
            Route::post('/retry', [TransactionControllerV2::class, 'retry']);
            Route::post('/complete', [TransactionControllerV2::class, 'complete']);
            Route::post('/fail', [TransactionControllerV2::class, 'fail']);
        });
    });

    Route::prefix('/subscription-types')->group(function () {
        Route::get('/', [SubscriptionTypeControllerV2::class, 'index']);
        Route::get('/default', [SubscriptionTypeControllerV2::class, 'defaultSubscriptionType']);
    });

    Route::prefix('/subscriptions')->group(function () {
        Route::get('/{subscription}', [SubscriptionControllerV2::class, 'show']);
        Route::get('/', [SubscriptionControllerV2::class, 'index']);
    });

    Route::prefix('/earning-accounts')->group(function () {
        Route::get('/', [EarningAccountControllerV2::class, 'index']);
        Route::get('/{earning_account}', [EarningAccountControllerV2::class, 'show']);
    });

    Route::prefix('/cashbacks')->group(function () {
        Route::get('/', [CashbackControllerV2::class, 'index']);
        Route::get('/{cashback}', [CashbackControllerV2::class, 'show']);
    });

    Route::get('/airtime/accounts', [AirtimeControllerV2::class, 'accounts']);
    Route::get('/utility/accounts', [UtilityControllerV2::class, 'accounts']);

    Route::prefix('/accounts')->group(function () {

        Route::prefix('/{accountId}')->group(function () {
            Route::get('/details', [AccountControllerV2::class, 'show']);

            Route::get('/airtime-accounts', [AccountControllerV2::class, 'airtimeAccounts']);
            Route::get('/utility-accounts', [AccountControllerV2::class, 'utilityAccounts']);

            Route::get('/current-subscription', [AccountControllerV2::class, 'currentSubscription']);

            Route::get('/earnings', [AccountControllerV2::class, 'earnings']);
        });
    });

    //  DASHBOARD ROUTES
    Route::prefix('/dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/revenue-chart', [DashboardController::class, 'revenueChart']);
    });

    // Utilities
    Route::get('/earnings/rates', [ProductController::class, 'getEarningRates']);

    Route::get('/service-providers/balance', [ProductController::class, 'getServiceProviderBalance']);

});

