<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Web\{
    IndexController,
    AuthController,
    ProductMassDeleteController,
    ChangePriceController,
    SqlController,
    OpenCartUserController,
    ChangeImageController,
};

Route::get('/', [IndexController::class, 'index'])->name('index');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/mass-delete', [ProductMassDeleteController::class, 'massDelete'])->name('massDelete');
    Route::post('/mass-delete/push', [ProductMassDeleteController::class, 'pushDelete'])->name('massDelete.push');
    Route::get('/mass-delete/show', [ProductMassDeleteController::class, 'showProgress'])->name('massDelete.progress');

    Route::get('/change-price', [ChangePriceController::class, 'display'])->name('changePrice.display');
    Route::post('/change-price/push', [ChangePriceController::class, 'push'])->name('changePrice.push');
    Route::get('/change-price/show', [ChangePriceController::class, 'progress'])->name('changePrice.progress');

    Route::get('/sql', [SqlController::class, 'display'])->name('sql.display');
    Route::get('/sql/results', [SqlController::class, 'insertQueries'])->name('sql.insertQueries');

    Route::get('/oc-users', [OpenCartUserController::class, 'all'])->name('oc-users.all');

    Route::get('/images', [ChangeImageController::class, 'show']);
    Route::post('/images', [ChangeImageController::class, 'push'])->name('images.push');
    Route::get('/images/{ean}', [ChangeImageController::class, 'progress'])->name('images.progress');

});

