<?php

namespace App\Http\Controllers\OpenCart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group OpenCart
 * @subgroup Language
 */
class LanguageController extends Controller
{
    protected $database;
    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
    }

    /**
     * List languages.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @response 200 {
     *   "data": [
     *     {
     *       "language_id": 1,
     *       "name": "English",
     *       "code": "en-gb"
     *     }
     *   ]
     * }
     */
    public function all(Request $request) {
        $items = DB::connection($this->database)
            ->table('oc_language')
            ->get();

        return response()->json([
            'data' => $items
        ], 200);
    }
}
