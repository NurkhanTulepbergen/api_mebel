<?php

namespace App\Http\Controllers\OpenCart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManufacturerController extends Controller
{
    public $availableDatabases;
    public $database;

    /**
     * @group OpenCart
     * @subgroup Manufacturer
     */
    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
        if(str_contains($this->database, 'jv')) abort(400, 'Your current database is JV. You cant use this endpoint here');
    }

    /**
     * List manufacturers.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @response 200 [
     *   {
     *     "manufacturer_id": 7,
     *     "name": "Acme Furniture",
     *     "image": "catalog/manufacturer/acme.png"
     *   }
     * ]
     * @response 400 scenario="Invalid database" {
     *   "message": "Your current database is JV. You cant use this endpoint here"
     * }
     */
    public function all() {
        $items = DB::connection($this->database)
            ->table('oc_manufacturer')
            ->get();

        return response()->json($items);
    }
}
