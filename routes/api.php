<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'users'

], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/', [AuthController::class, 'register']);
    Route::get('/', [AuthController::class, 'userList']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::put('/profile', [AuthController::class, 'updateUserProfile']);

    Route::get('/{id}', [AuthController::class, 'userProfile']);
    Route::delete('/{id}', [AuthController::class, 'destroy']);
    Route::put('/{id}', [AuthController::class, 'updateUser']);
});

Route::get('/products', [ProductController::class, 'products']);

Route::get('/products/{slug}', [ProductController::class, 'productById']);

Route::group([
    'middleware' => 'api',
    'prefix' => 'orders'
], function () {
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/myorders', [OrderController::class, 'myOrders']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::put('/{id}/pay', [OrderController::class, 'updatePayment']);
    Route::put('/{id}/deliver', [OrderController::class, 'updateDeliver']);
});

Route::middleware('auth:api')->get('/config/paypal', function () {
    return response()->json(env('PAYPAL_CLIENT_ID', 0), 200);
});
