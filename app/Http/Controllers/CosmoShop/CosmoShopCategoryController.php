<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use app\Http\Traits\ChangeFieldsTrait;

/**
 * @group CosmoShop
 * @subgroup CosmoShopCategory
 */
class CosmoShopCategoryController extends Controller
{
    public $tableName = 'shoprubriken';
    public $pk = 'rubid';
    public $mapping = [
        'category_id' => 'rubid',
        'category_code' => 'rubnum',
        'category_image' => 'rubbild',
        'sort_type' => 'rubsort',
        'mode' => 'rubmode',
        'sort_order' => 'ruborder',
        'customer_groups' => 'rub_kdgruppen',
        'payment_selection' => 'rub_bpsel',
        'parent_path' => 'rub_parent',
        'url_key' => 'ruburlkey',
        'parent_id' => 'parentid',
        'updated_at' => 'geaendert',
        'article_sort_string' => 'rubartikel_sort_str',
        'disable_amazon_payment' => 'no_amazon_payment',
        'mega_menu_data' => 'mega_menu',
        'disable_indexing' => 'noindex',
        'external_key' => 'key_extern',
        'disable_additional_popup' => 'rub_zusatz_no_popup',
        'show_additional_bottom' => 'rub_zusatz_bottom',
        'disable_article_listing' => 'disable_articlelisting'
    ];

    public $contentMapping = [
        'category_code_ref' => 'rubnumref',
        'category_id' => 'rubid',
        'language' => 'rubsprache',
        'category_name' => 'rubnam',
        'description' => 'rubtext',
        'meta_keywords' => 'keywords',
        'short_description' => 'rubtext_kurz',
        'url_key' => 'urlkey',
        'slider_id' => 'slider_id',
    ];
    public $flippedMapping;
    public $flippedContentMapping;

    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
        $this->flippedMapping = array_flip($this->mapping);
        $this->flippedContentMapping = array_flip($this->contentMapping);
    }

    public function create(Request $request) {
        $db = DB::connection($this->database);
        $validated = $request->validate([
            'category_code' => 'required|string|max:200',
            'category_image' => 'nullable|string|max:250',
            'sort_type' => 'nullable|integer|min:0|max:127',
            'mode' => 'nullable|integer|min:0|max:127',
            'sort_order' => 'nullable|integer|min:0',
            'customer_groups' => 'nullable|string|max:250',
            'payment_selection' => 'nullable|string|max:255',
            'parent_path' => 'required|string|max:250',
            'url_key' => 'required|string|max:100',
            'parent_id' => 'nullable|integer|min:0',
            'updated_at' => 'nullable|date',
            'article_sort_string' => 'nullable|string|max:250',
            'disable_amazon_payment' => 'nullable|boolean',
            'mega_menu_data' => 'nullable',
            'disable_indexing' => 'nullable|boolean',
            'external_key' => 'nullable|string|max:255',
            'disable_additional_popup' => 'nullable|boolean',
            'show_additional_bottom' => 'nullable|boolean',
            'disable_article_listing' => 'nullable|boolean'
        ]);
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
            'category_code' => 'string|max:200',
            'category_image' => 'nullable|string|max:250',
            'sort_type' => 'nullable|integer|min:0|max:127',
            'mode' => 'nullable|integer|min:0|max:127',
            'sort_order' => 'nullable|integer|min:0',
            'customer_groups' => 'nullable|string|max:250',
            'payment_selection' => 'nullable|string|max:255',
            'parent_path' => 'required|string|max:250',
            'url_key' => 'required|string|max:100',
            'parent_id' => 'nullable|integer|min:0',
            'updated_at' => 'nullable|date',
            'article_sort_string' => 'nullable|string|max:250',
            'disable_amazon_payment' => 'nullable|boolean',
            'mega_menu_data' => 'nullable',
            'disable_indexing' => 'nullable|boolean',
            'external_key' => 'nullable|string|max:255',
            'disable_additional_popup' => 'nullable|boolean',
            'show_additional_bottom' => 'nullable|boolean',
            'disable_article_listing' => 'nullable|boolean'
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
        $affected = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->update($mappedData);
        dd($affected);
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
        $products = DB::connection($this->database)
            ->table($this->tableName)
            ->select([
                'rubid as category_id',
                'rubnum as category_code'
            ])
            ->paginate(500);

        return response()->json([
            'data' => $products,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], 200);
    }

    public function read(int $id, Request $request) {
        $db = DB::connection($this->database);
        $categoryfields = [
            'rubid', 'rubnum', 'rubbild', 'rubsort', 'rubmode', 'ruborder',
            'rub_kdgruppen', 'rub_bpsel', 'rub_parent', 'ruburlkey', 'parentid',
            'geaendert', 'rubartikel_sort_str', 'no_amazon_payment', 'noindex',
            'key_extern', 'rub_zusatz_no_popup', 'rub_zusatz_bottom', 'disable_articlelisting'
        ];
        $contentFields = [
            'rubnumref',
            'rubsprache',
            'rubnam',
            'rubtext',
            'keywords',
            'rubtext_kurz',
            'urlkey',
            'slider_id',
        ];
        $allFields = [];
        foreach($categoryfields as $field) {
            array_push($allFields, 'c.'.$field.' as '.$this->flippedMapping[$field]);
        }
        foreach($contentFields as $field) {
            array_push($allFields, 'ct.'.$field.' as '.$this->flippedContentMapping[$field]);
        }
        // dd($allFields);
        $item = $db->table('shoprubriken as c')
            ->leftJoin('shoprubrikencontent as ct', 'c.rubid', '=', 'ct.rubid')
            ->select($allFields)
            ->where('c.rubid', $id)
            ->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }
        return response()->json([
            'data' => $item
        ], 200);
    }

    public function getFullName(Request $request) {
        $db = DB::connection($this->database);

        // 1) Считаем все категории вместе с контентом
        $all = $db->table('shoprubriken as c')
            ->leftJoin('shoprubrikencontent as ct', 'c.rubid', '=', 'ct.rubid')
            ->select([
                'c.rubid as category_id',
                'c.parentid as parent_id',
                'ct.rubnam as category_name',
                'ct.urlkey as url',
                'ct.rubtext as description'
            ])
            ->orderBy('c.parentid')
            ->orderBy('c.rubid')
            ->get();

        // 2) Собираем справочник по id
        $refs = [];
        foreach ($all as $row) {
            $refs[$row->category_id] = $row;
        }

        // 3) Для каждой категории строим full_name — путь имён до неё
        foreach ($all as $row) {
            $path = [];
            $current = $row;

            // поднимаемся по цепочке parent_id до корня
            while ($current->parent_id != 0 && isset($refs[$current->parent_id])) {
                $parent = $refs[$current->parent_id];
                array_unshift($path, $parent->category_name);
                $current = $parent;
            }

            // добавляем собственное имя в конец пути
            $path[] = $row->category_name;

            // сохраняем
            $row->full_name = $path;
        }

        // 4) Возвращаем плоский массив со всеми полями + full_name
        return response()->json([
            'categories' => $all,
            'count' => sizeof($all),
        ], 200);
    }

    public function getAllChildren(Request $request) {
        $db = DB::connection($this->database);

        // 1) Считаем все категории вместе с контентом
        $all = $db->table('shoprubriken as c')
            ->leftJoin('shoprubrikencontent as ct', 'c.rubid', '=', 'ct.rubid')
            ->select([
                'c.rubid as category_id',
                'c.parentid as parent_id',
                'ct.rubnam as category_name',
                'ct.urlkey as url',
                'ct.rubtext as description'
            ])
            ->orderBy('c.parentid')
            ->orderBy('c.rubid')
            ->get();

        // 2) Собираем справочник по id
        $refs = [];
        foreach ($all as $row) {
            $refs[$row->category_id] = $row;
        }

        // 3) Для каждой категории строим full_name — путь имён до неё
        foreach ($all as $row) {
            $path = [];
            $current = $row;

            // поднимаемся по цепочке parent_id до корня
            while ($current->parent_id != 0 && isset($refs[$current->parent_id])) {
                $parent = $refs[$current->parent_id];
                array_unshift($path, $parent->category_name);
                $current = $parent;
            }

            // добавляем собственное имя в конец пути
            $path[] = $row->category_name;

            // сохраняем
            $row->full_name = $path;
        }
        $data = [];
        foreach($all as $category) {
            if($all->where('parent_id', $category->category_id)->count() == 0) {
                array_push($data, $category);
            }
        }

        // 4) Возвращаем плоский массив со всеми полями + full_name
        return response()->json([
            'categories' => $data,
            'count' => sizeof($data),
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
}
