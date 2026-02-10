<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IndexController extends Controller
{
    public function index() {
    $links = [
        ['href' => '/docs', 'label' => 'API Documentation'],
        ['href' => '/mass-delete', 'label' => 'Mass Delete'],
        ['href' => '/domains', 'label' => 'Domain Settings'],
    ];

    if(Auth::check())
        $links[] = ['href' => '/logout', 'label' => 'Logout'];
    else
        $links[] = ['href' => '/login', 'label' => 'Login'];

    return view('index', compact('links'));
}
}
