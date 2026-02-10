<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{
    DB,
    Config,
};

use App\Models\{
    Domain,
};

class SqlController extends Controller
{
    public function display()
    {
        $domains = Domain::getSeparatedDomains();
        return view('sql.display', compact('domains'));
    }

    public function insertQueries(Request $request) {
        $validated = $request->validate([
            'query' => 'required|string'
        ]);

        set_time_limit(0);

        if (!isset($request->base_ids)) {
            return back()->withErrors(['base_ids' => ['Вы должны выбрать хотя-бы одну базу']]);
        }

        $query = trim($validated['query']);
        $isSelect = stripos($query, 'select') === 0;

        // Для SELECT добавляем LIMIT, если его нет
        if ($isSelect && !preg_match('/\blimit\b/i', $query)) {
            $query .= ' LIMIT 500';
        }

        $domains = Domain::with('database')->whereIn('id', $request->base_ids)->get();
        $responses = [];

        foreach ($domains as $domain) {
            $db = DB::connection($domain->name);
            if ($isSelect) {
                // SELECT → возвращаем данные
                $responses[$domain->name] = $db->select($query);
            } else {
                // UPDATE / INSERT / DELETE → возвращаем число изменённых строк
                $responses[$domain->name] = [
                    $db->affectingStatement($query)
                ];
            }
        }

        return view('sql.showResponse', compact('responses', 'isSelect'));
    }
}
