<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\{
    Redis,
};

use App\Jobs\OpenCart\ChangeImagesJob;

use App\Models\{
    Domain,
};

class ChangeImageController extends Controller
{
    public function show() {
        return view('changeImages.show', [
            'domains' => Domain::getXl(),
        ]);
    }

    public function push(Request $request) {
        $validated = $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|mimes:jpg,webp,png,jpeg',
            'ean'      => 'required|string'
        ]);
        $ean = $validated['ean'];
        $paths = [];
        foreach ($validated['images'] as $image) {
            // storage/app/tmp
            $paths[] = $image->store('tmp');
        }

        $domains = Domain::getXl();

        foreach($domains as $domain) {
            Redis::hset($ean, $domain->name, -1);
            ChangeImagesJob::dispatch(
                ean: $ean,
                images: $paths,
                domain: $domain
            );
        }

        return redirect(route('images.progress', ['ean' => $ean]));
    }

    public function progress() {
        $domains = Domain::getXl();
        return view('changeImages.progress', compact('domains'));
    }
}
