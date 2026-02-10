<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\{
  CheckAPIToken,
  ApiLoggingMiddleware,
  CheckAccessTokenExpiration,
  IpMiddleware,
  ProfileJsonResponse,
};

use App\Http\Controllers\OpenCart\{
    LanguageController,
    CategoryDescriptionController,
    ProductDescriptionController,
    UrlAliasController,
    ProductController,
    ManufacturerController,
    ProductImageController,
    ProductToCategoryController,
};
Route::prefix('open-cart')->group(function () {
    Route::middleware([
        ApiLoggingMiddleware::class,
        'throttle:request-limit'
    ])->group(function () {
        Route::middleware([
            CheckAccessTokenExpiration::class,
            'auth:sanctum',
        ])->group(function () {
            // Language
            Route::controller(ProductController::class)->group(function () {
            Route::prefix('product')->group(function () {
                Route::post('/', 'create');
                Route::put('/price/{id}', 'changePrice');
                Route::patch('/{id}', 'update');
                Route::get('/view/{id}', 'view');
                Route::delete('/mass-delete', 'massDeleteBatch');
                Route::delete('/{id}', 'delete');
                Route::get('/paginate', 'paginate');
                Route::get('/find-many', 'findManyIds');
                Route::get('/since', 'getProductsUpdatedAfter');
                Route::get('/language-stats', 'languageStats');
                Route::get('/with-custom-output', 'withCustomOutput');
                Route::get('/with-custom-output/help', 'withCustomOutputHelp');
            });
            });

            Route::get('/manufacturer', [ManufacturerController::class, 'all']);

            // Language
            Route::controller(LanguageController::class)->group(function () {
            Route::prefix('language')->group(function () {
                Route::get('/', 'all');
            });
            });

            // ProductDescription
            Route::controller(ProductDescriptionController::class)
            ->prefix('product-description')
            ->group(function () {
                Route::get('/', 'paginate');
                Route::post('/', 'create');
                Route::patch('/', 'update');
                Route::delete('/', 'delete');
                Route::get('/view', 'read');
            });


            // ProductDescription
            Route::controller(ProductImageController::class)
            ->prefix('product-image')
            ->group(function () {
                Route::post('/', 'create');
                Route::delete('/{id}', 'delete');
                Route::get('/{id}', 'read');
            });

            // ProductToCategory
            Route::controller(ProductToCategoryController::class)
            ->prefix('product-to-category')
            ->group(function () {
                Route::post('/', 'create');
                Route::delete('/{id}', 'delete');
                Route::get('/{id}', 'read');
            });

            // CategoryDescription
            Route::controller(CategoryDescriptionController::class)->group(function () {
            Route::prefix('category-description')->group(function () {
                Route::get('/paginate', 'paginate');
                Route::post('/', 'create');
                Route::put('/', 'update');
                Route::delete('/{id}/{language}', 'delete');
                Route::get('/{id}', 'read');
            });
            });

            // UrlAlias
            Route::controller(UrlAliasController::class)->group(function () {
            Route::prefix('url')->group(function () {
                Route::post('/', 'create');
                Route::get('/paginate', 'paginate');
                Route::put('/{id}', 'update');
                Route::delete('/{id}', 'delete');
                Route::get('/{id}', 'read');
            });
            });
        });
    });
});
