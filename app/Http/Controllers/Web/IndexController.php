<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class IndexController extends Controller
{
    public function index()
    {
        $links = [
            ['href' => '/docs', 'label' => 'API Documentation'],
            ['href' => '/mass-delete', 'label' => 'Mass Delete'],
            ['href' => '/change-price', 'label' => 'Change Price'],
            ['href' => '/admin', 'label' => 'Admin panel'],
            ['href' => '/images', 'label' => 'XL images'],
        ];

        if (Auth::check())
            $links[] = ['href' => '/logout', 'label' => 'Logout'];
        else
            $links[] = ['href' => '/login', 'label' => 'Login'];

        return view('index', compact('links'));
    }
}
