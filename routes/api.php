<?php

use App\Http\Controllers\API\V1\ProductController;
use App\Models\Transaction;
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
    Route::post('/products/utility-purchase', ProductController::class);
    Route::get('/transactions', function() {
        return response()->json(['transactions' => Transaction::all()]);
    });
});
