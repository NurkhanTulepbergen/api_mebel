<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ShopDatabaseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $shop = $request->route('shop'); // например xl.de

        // Если это XL.DE → Делаем обход MySQL
        if ($shop === 'xl.de') {

            // Определяем, какое действие вызывает API
            $action = $request->route()->getActionMethod();

            // === PRICE UPDATE ===
            if ($action === 'changePrice') {

                $productId = $request->route('id');
                $price     = $request->input('price');

                $response = Http::get("https://www.xlmoebel.de/api/update_price.php", [
                    'token'      => env('XLDE_TOKEN'),
                    'product_id' => $productId,
                    'price'      => $price,
                ]);

                return response()->json([
                    'success' => $response->ok(),
                    'via'     => 'http',
                    'shop'    => $shop,
                    'data'    => $response->json(),
                ]);
            }

            // === GET PRODUCT BY MODEL ===
            if ($action === 'withCustomOutput') {

                $models = $request->input('models');

                $response = Http::get("https://www.xlmoebel.de/api/get_product.php", [
                    'token'   => env('XLDE_TOKEN'),
                    'models'  => implode(",", $models),
                ]);

                return response()->json([
                    'success' => $response->ok(),
                    'via'     => 'http',
                    'shop'    => $shop,
                    'data'    => $response->json(),
                ]);
            }

            // Если другое действие — можно добавить обработку позже
        }

        // Для всех остальных магазинов — обычный MySQL-путь
        return $next($request);
    }
}
