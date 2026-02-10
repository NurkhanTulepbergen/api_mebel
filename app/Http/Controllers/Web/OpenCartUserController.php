<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{
    DB,
};

use App\Http\Traits\DynamicConnectionTrait;

use App\Models\{
    Domain,
};

class OpenCartUserController extends Controller
{
    use DynamicConnectionTrait;
    public function all() {
        set_time_limit(0);
        $domains = Domain::getXl()->keyBy('name');
        $users = [];
        foreach($domains as $name => $domain) {
            $users[$name] = $this->getDynamicConnection($domain->database)
                ->table('oc_user')
                ->count();
        }
        return view('ocUsers.all', compact('users'));

    }
}
