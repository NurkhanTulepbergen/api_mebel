<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\{
    CheckAPIToken,
    ApiLoggingMiddleware,
    CheckAccessTokenExpiration,
    IpMiddleware,
    ProfileJsonResponse,
};

use App\Http\Controllers\CosmoShop\{
    MediaController,
    AdminController,
};

use App\Http\Controllers\{
    ProductToCategoryController,
    LogController,
    UserController,
};

use Illuminate\Support\Facades\Redis;

require __DIR__ . '/api/open-cart.php';
require __DIR__ . '/api/cosmo-shop.php';

Route::middleware([
    'throttle:request-limit',
    ApiLoggingMiddleware::class,
    // IpMiddleware::class
    ])->group(function () {

    Route::controller(AdminController::class)->group(function () {
        Route::get('/allProducts', 'allTablesWithArticleId');
    });
    Route::controller(UserController::class)->group(function () {
        Route::post('/login', 'login');
        Route::post('/refresh-token', 'refreshToken');
        Route::post('/register', 'register');
        Route::get('/me', 'showUser')->middleware([CheckAccessTokenExpiration::class, 'auth:sanctum']);
        // Route::post('/attach-two-factor', 'attachTwoFactor');
    });
});

Route::get('/mass-delete', function() {
    return response()->json([
        'status' => Redis::get("mass_delete_job:status") ?? 'not started',
        'messages' => Redis::lrange("mass_delete_job:messages", 0, -1) ?? [],
    ]);
});
Route::get('/change-price', function() {
    return response()->json([
        'status' => Redis::get("change_price_job:status") ?? 'not started',
        'messages' => Redis::lrange("change_price_job:messages", 0, -1) ?? [],
    ]);
});
Route::get('/images/{ean}', function($ean) {
    return response()->json(Redis::hgetall($ean));
});

Route::controller(LogController::class)->group(function () {
    Route::get('/send-logs', 'sendLogs');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
