<?php

use App\Http\Controllers\API\V1\AirtimeController;
use App\Http\Controllers\API\V1\EnterpriseController;
use App\Http\Controllers\API\V1\FloatController;
use App\Http\Controllers\API\V1\MerchantController;
use App\Http\Controllers\API\V1\SubscriptionController;
use App\Http\Controllers\API\V1\TransactionController;
use App\Http\Controllers\API\V1\UtilityController;
use App\Http\Controllers\API\V1\VoucherController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function(Request $request) {
    return $request->user();
});

Route::/*middleware('auth.jwt')->*/prefix('/v1')->name('api.')->group(function() {
    Route::prefix('/products')->group(function() {
        Route::post('/airtime', AirtimeController::class);
        Route::post('/airtime/status/callback', [AirtimeController::class, 'airtimeStatusCallback']);
        Route::post('/utility', UtilityController::class);
        Route::post('/subscription', SubscriptionController::class);

        Route::prefix('/voucher')->controller(VoucherController::class)->group(function() {
            Route::post('/top-up', 'topUp');
            Route::post('/disburse', 'disburse');
        });

        Route::post('/float/top-up', [FloatController::class, 'topUp']);
    });

    Route::prefix('/enterprises')->group(function() {
        Route::get('/', [EnterpriseController::class, 'index']);
        Route::post('/', [EnterpriseController::class, 'store']);
        Route::post('/accounts', [EnterpriseController::class, 'storeAccount']);
        Route::get('/{enterprise}', [EnterpriseController::class, 'show']);
        Route::put('/{enterprise}', [EnterpriseController::class, 'update']);
    });

    Route::get('/transactions', [TransactionController::class, 'index']);
});
