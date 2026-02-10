<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Domain;
/**
 * @group CosmoShop
 * @subgroup Media
 */
class MediaController extends Controller
{
    public $tableName = \TableName::Media->value;
    public $pk = 'id';
    public $mapping = [
        'id' => 'id',
        'key' => 'key',
        'type' => 'art',
        'media_directory' => 'typ',
        'filename' => 'dateiname',
        'extension' => 'endung',
        'sort_order' => 'order',
        'width' => 'breite',
        'height' => 'hoehe',
        'variant_assignment' => 'zuordnung',
        'version' => 'version',
        'updated_at' => 'timestamp',
    ];
    public $flippedMapping;
    public $database;

    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
        $this->flippedMapping = array_flip($this->mapping);
    }
    function changeFields($collection) {
        $mappedProduct = [];
        foreach ($this->flippedMapping as $dbField => $apiField) {
            if(isset($collection->$dbField)) $mappedProduct[$apiField] = $collection->$dbField;
        }
        return $mappedProduct;
    }

    public function create(Request $request) {
        $db = DB::connection($this->database);
        $validated = $request->validate([
            'key'                => 'required|string|max:250',
            'type'               => 'required|string|max:20',
            'media_directory'    => 'required|string|max:20',
            'filename'           => 'required|string|max:250',
            'extension'          => 'required|string|max:20',
        ]);
        $validated['width'] = 0;
        $validated['height'] = 0;
        $validated['updated_at'] = now();
        $validated['version'] = 1;
        $validated['sort_order'] = 0;

        if($validated['media_directory'] == 'zg' || $validated['media_directory'] == 'z') {
            $imageCount = $db
                ->table($this->tableName)
                ->where($this->mapping['key'], $validated['key'])
                ->where($this->mapping['type'], $validated['type'])
                ->where($this->mapping['media_directory'], $validated['media_directory'])
                ->count();
            $validated['sort_order'] = $imageCount++;
        }

        if($validated['sort_order'] == 0) $validated['variant_assignment'] = '|';

        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedData[$this->mapping[$key]] = $value;
            }
        }
        $db->beginTransaction();
        $id = $db
            ->table($this->tableName)
            ->insertGetId($mappedData);
        $product = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first();

        if (!$product) {
            $db->rollBack();
            return response()->json(['message' => 'Failed to create item'], 500);
        }
        $db->commit();
        return response()->json([
            'message' => 'Item was created',
            'data' => $this->changeFields($product)
        ], 201);
    }

    public function update(int $id, Request $request) {
        $db = DB::connection($this->database);
        $validated = $request->validate([
            'key'                => 'string|max:250',
            'type'               => 'string|max:20',
            'media_directory'    => 'string|max:20',
            'filename'           => 'string|max:250',
            'extension'          => 'string|max:20',
            'sort_order'         => 'integer',
            'variant_assignment' => 'string|max:100',
        ]);
        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedData[$this->mapping[$key]] = $value;
            }
        }
        $oldItem = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first();

        $oldItem = $this->changeFields($oldItem);

        $validated['updated_at'] = now();
        $validated['version'] = $oldItem['version']+1;

        $affected = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->update($mappedData);
        if ($affected > 1) {
            $db->rollBack();
            return response()->json(['message' => 'Item not found'], 404);
        }
        $db->commit();
        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->select(array_keys($mappedData))
            ->first();
        return response()->json([
            'message' => 'updated',
            'old_item' => $this->changeFields($oldItem),
            'new_item' => $this->changeFields($item),
        ], 201);
    }

    public function paginate(Request $request) {
        $viewedColumns = [
            'id', 'key', 'art', 'typ', 'dateiname', 'endung', 'order', 'timestamp',
        ];
        $products = DB::connection($this->database)
            ->table($this->tableName)
            ->orderBy($this->mapping['updated_at'], 'desc')
            ->select($viewedColumns)
            ->paginate(50);

        $mappedItems = $products->map(function ($item) {
            return $this->changeFields($item);
        });

        return response()->json([
            'data' => $mappedItems
        ], 200);
    }

    public function getByKey(Request $request) {
        $validated = $request->validate([
            'key'  => 'required|string'
        ]);
        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->where('key', $validated['key'])
            ->get();
        if (sizeof($items) == 0) {
            return response()->json([
                'main_images' => [],
                'additional_images' => []
            ], 200);
        }
        $itemKey = $items[0]->key;
        $data = [];
        $domain = Domain::getByName($this->database);
        $additionalImages = [
            "/{$domain->link}/cosmoshop/default/pix/a/z/".$itemKey.'/g' => [],
            "/{$domain->link}/cosmoshop/default/pix/a/z/".$itemKey => [],
        ];

        foreach($items as $item) {
            $filename = $item->dateiname.'.'.$item->endung;
            $directory = "/{$domain->link}/cosmoshop/default/pix/a/";
            if($item->typ == 'z' || $item->typ == 'zg') {
                $directory = $directory.'z/'.$itemKey;
                if($item->typ == 'zg') {
                    $directory = $directory.'/g';
                }
                array_push($additionalImages[$directory], $filename);
            } else {
                $directory = $directory.$item->typ;
                $data[$directory] = $filename;
            }

        }
        return response()->json([
            'main_images' => $data,
            'additional_images' => $additionalImages
        ], 200);
    }

    public function getByArticleIds(Request $request) {
        $validated = $request->validate([
            'article_ids' => 'required|array',
            'article_ids.*' => 'required|integer',
        ]);
        $domain = Domain::getByName($this->database);
        $db = DB::connection($this->database);
        $images = $db->table('shopartikel as product')
            ->join('shopmedia as images', 'product.artikelnr', '=', 'images.key')
            ->whereIn('product.artikelid', $validated['article_ids'])
            ->where('images.art', 'artikel')
            ->where('images.typ', 'n')
            ->get(['images.dateiname', 'images.endung', 'product.artikelid', 'product.artikelnr']);
        $data = array_fill_keys($validated['article_ids'], null);
        foreach($images as $image) {
            $filename = "{$image->dateiname}.{$image->endung}";
            $directory = "https://{$domain->link}/cosmoshop/default/pix/a/n/{$filename}";
            $data[$image->artikelid] = $directory;
        }
        return response()->json($data, 200);
    }

    public function getAdditionalImages(Request $request) {
        $validated = $request->validate([
            'article_ids' => ['required_without:article_numbers', 'array'],
            'article_numbers' => ['required_without:article_ids', 'array'],
            'article_ids.*' => ['integer'],
        ]);
        if($request->has('article_ids') && $request->has('article_numbers')) {
            return response()->json([
                'message' => 'You can choose article_ids OR article_numbers, but not BOTH.',
            ], 422);
        }
        $domain = Domain::getByName($this->database);
        $db = DB::connection($this->database);
        if(isset($validated['article_ids'])) {
            $requestedIds = $validated['article_ids'];

            $images = $db->table('shopartikel as product')
                ->join('shopmedia as images', 'product.artikelnr', '=', 'images.key')
                ->whereIn('product.artikelid', $requestedIds)
                ->where('images.art', 'artikel')
                ->where('images.typ', 'z')
                ->orderBy('images.order', 'asc')
                ->get(['images.dateiname', 'images.endung', 'product.artikelid as article_id', 'product.artikelnr as product_number', 'images.order', 'images.key as image_key'])
                ->groupBy('article_id');
        } elseif(isset($validated['article_numbers'])) {
            $requestedIds = $validated['article_numbers'];

            $images = $db
                ->table($this->tableName)
                ->whereIn('key', $requestedIds)
                ->where('art', 'artikel')
                ->where('typ', 'z')
                ->orderBy('order', 'asc')
                ->get(['dateiname', 'endung', 'key as product_number'])
                ->groupBy('product_number');
        }
        $data = array_fill_keys($requestedIds, []);
        foreach($images as $key => $imageCollectoins) {
            foreach($imageCollectoins as $image) {
                $filename = "{$image->dateiname}.{$image->endung}";
                $directory = "https://{$domain->link}/cosmoshop/default/pix/a/z/{$image->product_number}/{$filename}";
                $data[$key][] = $directory;
            }
        }
        return response()->json($data, 200);
    }

    public function read(int $id, Request $request) {
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where('id', $id)
            ->first();
        if (!$item) {
            abort(404, "Item was not found");
        }
        return response()->json([
            'data' => $this->changeFields($item)
        ], 200);
    }

    public function delete(int $id, Request $request) {
        $db = DB::connection($this->database);
        $db->beginTransaction();
        $deleted = $db->table($this->tableName)->where($this->pk, $id)->delete();
        if(!$deleted) {
            $db->rollBack();
            abort(404, "Item was not found");
        }
        $db->commit();
        return response()->json([
            'message' => $deleted.' item(s) was deleted',
        ], 200);
    }

    public function deleteFromProduct(Request $request) {
        $db = DB::connection($this->database);
        $validated = $request->validate([
            'key' => 'required|string'
        ]);
        $db->beginTransaction();
        $items = $db->table($this->tableName)->where('key', $validated['key'])->get();
        $deleted = $db->table($this->tableName)->where('key', $validated['key'])->delete();
        if(!$deleted) {
            $db->rollBack();
            abort(404, "Item was not found");
        }
        $db->commit();
        return response()->json([
            'message' => $deleted.' item(s) was deleted',
            'deleted_items' => $items
        ], 200);
    }

    public function massDelete(Request $request) {
        $db = DB::connection($this->database);
        $db->beginTransaction();
        $deleted = $db->table($this->tableName)
            ->whereDate('timestamp', now())
            ->count();
        dd($deleted);
        if(!$deleted) {
            $db->rollBack();
            abort(404, "Item was not found");
        }
        $db->commit();
        return response()->json([
            'message' => $deleted.' item(s) was deleted',
        ], 200);
    }

    public function createGeneralImages(Request $request) {
        $validated = $request->validate([
            'article_number' => 'required|string',
            'filename'       => 'required|string'
        ]);
        $db = DB::connection($this->database);
        $db->beginTransaction();
        $types = [
            'v', 'n', 'g', 'flashzoomer'
        ];
        $now  = now();
        $rows = [];
        foreach ($types as $type) {
            $rows[] = [
                'key' => $validated['article_number'],
                'art' => 'artikel',
                'typ' => $type,
                'dateiname' => $validated['filename'],
                'endung' => 'jpg',
                'order' => 0,
                'breite' => 0,
                'hoehe' => 0,
                'version' => 1,
                'timestamp' => $now,
                'zuordnung' => '|'
            ];
        }
        $db->table($this->tableName)
            ->insert($rows);

        $items = $db->table($this->tableName)
            ->where('key', $validated['article_number'])
            ->where('timestamp', $now)
            ->get();
        $db->commit();
        $mappedItems = $items->map(function ($item) {
            return $this->changeFields($item);
        });
        return response()->json([
            'message' => 'Items was created',
            'data' => $mappedItems
        ], 201);
    }

    public function createAdditionalImages(Request $request) {
        $db = DB::connection($this->database);
        $validated = $request->validate([
            'article_number' => 'required|string',
            'filenames'       => 'required|array'
        ]);
        $now = now();
        $imageCount = $db
            ->table($this->tableName)
            ->where($this->mapping['key'], $validated['article_number'])
            ->where($this->mapping['type'], 'artikel')
            ->where($this->mapping['media_directory'], 'z')
            ->count();
        $types = [
            'z', 'zg'
        ];
        $rows = [];
        foreach($validated['filenames'] as $filename) {
            $variantAssignment = '';
            if($imageCount == 0) $variantAssignment = '|';
            foreach ($types as $type) {
                $rows[] = [
                    'key' => $validated['article_number'],
                    'art' => 'artikel',
                    'typ' => $type,
                    'dateiname' => $filename,
                    'endung' => 'jpg',
                    'order' => $imageCount,
                    'breite' => 0,
                    'hoehe' => 0,
                    'version' => 1,
                    'timestamp' => $now,
                    'zuordnung' => $variantAssignment
                ];
            }
            $imageCount++;
        }

        $db->table($this->tableName)
            ->insert($rows);

        $items = $db->table($this->tableName)
            ->where('key', $validated['article_number'])
            ->where('timestamp', $now)
            ->get();
        $db->commit();
        $mappedItems = $items->map(function ($item) {
            return $this->changeFields($item);
        });
        return response()->json([
            'message' => 'Items was created',
            'data' => $mappedItems
        ], 201);
    }

    public function doesFileExists(Request $request) {
        $validated = $request->validate([
            'type' => 'required|string',
            'extension' => 'required|string',
            'filenames' => 'required|array',
        ]);
        $validated['filenames'] = array_unique($validated['filenames']);
        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->where('typ', $validated['type'])
            ->where('endung', $validated['extension'])
            ->whereIn('dateiname', $validated['filenames'])
            ->pluck('dateiname')
            ->toArray();
        $notFound = [];
        if(sizeof($validated['filenames']) != sizeof($items)) {
            foreach($validated['filenames'] as $key) {
                if(!in_array($key, $items)) array_push($notFound, $key);
            }
        }
        $stats = [
            'requested' => sizeof($validated['filenames']),
            'found'     => sizeof($items),
            'not_found' => sizeof($notFound),
        ];
        return response()->json([
            'stats' => $stats,
            'found' => $items,
            'not_found' => $notFound,
        ], 200);
    }
}
