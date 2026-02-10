<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\{
  CheckAPIToken,
  ApiLoggingMiddleware,
  CheckAccessTokenExpiration,
  IpMiddleware,
  ProfileJsonResponse,
};

use App\Http\Controllers\CosmoShop\{
  ProductController,
  ProductPriceController,
  ProductContentController,
  ProductStockController,
  ProductCategoriesController,
  ProductVariantController,
  ProductVariantContentController,
  ProductAttributeController,
  AttributeController,
  ProductAttributeAssignmentController,
  AttributeContentController,
  PropertyController,
  ProductPropertyController,
  StockMovementController,
  ProductVariationController,
  ProductVariationContentController,
  ProductReviewController,
  ProductRecomendationController,
  ProductBestsellerController,
  ProductSupplierInfoController,
  ProductCustomerGroupController,
  UnitOfMeasurmentController,
  UnitOfMeasurmentContentController,
  UnitOfMeasurmentRelationController,
  CosmoShopCategoryController,
  CategoryContentController,
  MediaController,
  SeoController,
  OnlyNeededDataController,
  InfoController,
    ProductExtendedInformationController
};
Route::prefix('cosmo-shop')->group(function () {
    Route::middleware([
        ApiLoggingMiddleware::class,
        'throttle:request-limit'
    ])->group(function () {
    Route::middleware([
        CheckAccessTokenExpiration::class,
        'auth:sanctum',
    ])->group(function () {
        // CosmoShop Products
        Route::controller(ProductController::class)->group(function () {
        Route::prefix('product')->group(function () {
            Route::post('/', 'create');
            Route::get('/get-id', 'getId');
            Route::get('/get-by-value-with-custom-output', 'getCustomOutput');
            Route::get('/paginate', 'paginate');
            Route::get('/paginate-by-user', 'paginateAllByUser');
            Route::get('/paginate-ids', 'paginateIds');
            Route::delete('/mass-delete-batch', 'massDeleteBatch');
            Route::delete('/mass-delete', 'massDelete');
            Route::get('/get-by-name-and-ean', 'getByNameAndEan');
            Route::get('/full/{id}', 'fullProduct');
            Route::get('/since', 'getProductsUpdatedAfter');
            Route::delete('/delete-only-product/{id}', 'deleteOnlyProduct');
            Route::put('/{id}',  'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Product Prices
        Route::controller(ProductPriceController::class)->group(function () {
        Route::prefix('product-price')->group(function () {
            Route::put('/update/{id}', 'changePrice');
            Route::get('/get-price', 'getPrice');
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/mass-delete', 'update');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        Route::controller(ProductExtendedInformationController::class)->group(function () {
            Route::prefix('product-extended-info')->group(function () {

                // CREATE
                Route::post('/', 'create');

                // READ
                Route::get('/paginate', 'paginate');     // если нужна пагинация
                Route::get('/article/{article_id}', 'readByArticle');
                Route::get('/{id}', 'read');             // read one

                // UPDATE
                Route::put('/update/{id}', 'update');    // main update
                Route::put('/mass-update', 'massUpdate'); // если хочешь — могу написать метод

                // DELETE
                Route::delete('/{id}', 'delete');

            });
        });


        // CosmoShop Product Content
        Route::controller(ProductContentController::class)->group(function () {
        Route::prefix('product-content')->group(function () {
            Route::get('/get-url', 'getUrl');
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Product Stock
        Route::controller(ProductStockController::class)->group(function () {
        Route::prefix('product-stock')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Product Categories
        Route::controller(ProductCategoriesController::class)->group(function () {
        Route::prefix('product-category')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Product Variants
        Route::controller(ProductVariantController::class)->group(function () {
        Route::prefix('product-variant')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Product Variant Content
        Route::controller(ProductVariantContentController::class)->group(function () {
        Route::prefix('product-variant-content')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}/{language}', 'update');
            Route::delete('/{id}/{language}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Product Attribute
        Route::controller(ProductAttributeController::class)->group(function () {
        Route::prefix('product-attribute')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });
        // CosmoShop Attribute
        Route::controller(AttributeController::class)->group(function () {
        Route::prefix('attribute')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Product Attribute Assigment
        Route::controller(ProductAttributeAssignmentController::class)->group(function () {
        Route::prefix('product-attribute-assignment')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{article_id}/{attribute_id}', 'update');
            Route::delete('/{article_id}/{attribute_id}', 'delete');
            Route::get('/{article_id}/{attribute_id}', 'read');
        });
        });

        // CosmoShop Attribute
        Route::controller(AttributeContentController::class)->group(function () {
        Route::prefix('attribute-content')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}/{language}', 'update');
            Route::delete('/{id}/{language}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(PropertyController::class)->group(function () {
        Route::prefix('property')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(ProductPropertyController::class)->group(function () {
        Route::prefix('product-property')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{article_id}/{property_id}', 'update');
            Route::delete('/{article_id}/{property_id}', 'delete');
            Route::get('/{article_id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(StockMovementController::class)->group(function () {
        Route::prefix('stock-movement')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(ProductVariationController::class)->group(function () {
        Route::prefix('product-variation')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(ProductVariationContentController::class)->group(function () {
        Route::prefix('product-variation-content')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}/{language}', 'update');
            Route::delete('/{id}/{language}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(ProductReviewController::class)->group(function () {
        Route::prefix('product-review')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(ProductRecomendationController::class)->group(function () {
        Route::prefix('product-recomendation')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{primary_product_id}/{recomended_product_id}', 'update');
            Route::delete('/{primary_product_id}/{recomended_product_id}', 'delete');
            Route::get('/{primary_product_id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(ProductBestsellerController::class)->group(function () {
        Route::prefix('product-bestseller')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(ProductSupplierInfoController::class)->group(function () {
        Route::prefix('product-supplier-info')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(ProductCustomerGroupController::class)->group(function () {
        Route::prefix('product-customer-group')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{article_id}/{customer_group_id}', 'update');
            Route::delete('/{article_id}/{customer_group_id}', 'delete');
            Route::get('/{article_id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(UnitOfMeasurmentController::class)->group(function () {
        Route::prefix('unit-of-measurment')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(UnitOfMeasurmentContentController::class)->group(function () {
        Route::prefix('unit-of-measurment-content')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}/{language}', 'update');
            Route::delete('/{id}/{language}', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(UnitOfMeasurmentRelationController::class)->group(function () {
        Route::prefix('unit-of-measurment-relation')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{base_unit_id}/{sub_unit_id}', 'update');
            Route::delete('/{base_unit_id}/{sub_unit_id}', 'delete');
            Route::get('/{base_unit_id}', 'read');
        });
        });

        // CosmoShop Seo
        Route::controller(SeoController::class)->group(function () {
        Route::prefix('seo')->group(function () {
            Route::post('/', 'create');
            Route::get('/paginate', 'paginate');
            Route::put('/{id}', 'update');
            Route::delete('/', 'delete');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(CosmoShopCategoryController::class)->group(function () {
        Route::prefix('category')->group(function () {
            Route::get('/paginate', 'paginate');
            Route::get('/get-full-name', 'getFullName');
            Route::get('/get-all-children', 'getAllChildren');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop Properties
        Route::controller(CategoryContentController::class)->group(function () {
        Route::prefix('category-content')->group(function () {
            Route::get('/paginate', 'paginate');
            Route::get('/{id}', 'read');
        });
        });

        // CosmoShop OnlyNeededData
        Route::controller(OnlyNeededDataController::class)->group(function () {
        Route::prefix('only-needed-data')->group(function () {
            Route::get('/', 'paginate');
            Route::get('/show', 'showByNameAndEan');
            Route::put('/', 'update');
            Route::post('/', 'create');
            Route::delete('/', 'delete');
            Route::get('/find-many', 'findManyIds');
            Route::get('/{id}', 'show');
        });
        });

        // CosmoShop Media
        Route::controller(MediaController::class)->group(function () {
            Route::prefix('media')->group(function () {
                Route::get('/does-files-exists', 'doesFileExists');
                Route::post('/general-images', 'createGeneralImages');
                Route::post('/additional-images', 'createAdditionalImages');
                Route::get('/paginate', 'paginate');
                Route::post('/', 'create');
                Route::get('/get-by-key', 'getByKey');
                Route::get('/get-by-article-ids', 'getByArticleIds');
                Route::get('/additional-images', 'getAdditionalImages');
                Route::get('/{id}', 'read');
                Route::put('/{id}', 'update');
                Route::delete('/product', 'deleteFromProduct');
                Route::delete('/mass-delete', 'massDelete');
                Route::delete('/{id}', 'delete');
            });
        });

        Route::controller(InfoController::class)->group(function () {
            Route::get('/get-info/database', 'database');
            Route::prefix('get-info')->group(function () {
                Route::get('/product', 'productFields');
                Route::get('/product-prices', 'productPricesFields');
                Route::get('/product-content', 'productContentFields');
                Route::get('/product-stock', 'productStockFields');
                Route::get('/product-categories', 'productCategoriesFields');
                Route::get('/product-variants', 'productVariantFields');
                Route::get('/product-variant-content', 'productVariantContentFields');
                Route::get('/product-attributes', 'productAttributesFields');
                Route::get('/attributes', 'AttributesFields');
                Route::get('/product-attributes-assignment', 'productAttributesAssignmentFields');
                Route::get('/attribute-content', 'attributeContentFields');
                Route::get('/property', 'propertiesFields');
                Route::get('/product-property', 'productPropertiesFields');
                Route::get('/stock-movements', 'stockMovementsFields');
                Route::get('/product-variations', 'productVariationFields');
                Route::get('/product-review', 'productReviewFields');
                Route::get('/product-recomendation', 'productRecomendationFields');
                Route::get('/product-bestseller', 'productBestsellerFields');
                Route::get('/product-supplier-info', 'productSupplierInfoFields');
                Route::get('/product-customer-groups', 'productCustomerGroupsFields');
                Route::get('/unit-of-measurment', 'unitOfMeasurmentFields');
                Route::get('/unit-of-measurment-content', 'unitOfMeasurmentContentFields');
                Route::get('/unit-of-measurment-relation', 'unitOfMeasurmentRelationtFields');
                Route::get('/category', 'categoryFields');
                Route::get('/category-content', 'categoryContentFields');
            });
        });
    });
    });
});
