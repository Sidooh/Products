<?php

use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\TransactionController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('/v1')->name('api.')->group(function() {
    Route::post('/products', ProductController::class);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/vouchers', [VoucherController::class, 'index']);
});
