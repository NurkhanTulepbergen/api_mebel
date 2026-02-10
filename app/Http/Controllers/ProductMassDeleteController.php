<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Jobs\ProductMassDeleteViaExcelJob;
use Illuminate\Support\Facades\Redis;

class ProductMassDeleteController extends Controller
{
    public function massDelete() {
        $jv = config('domains.jv');
        $xl = config('domains.xl');
        $xl['turkey'] = 'furniture-from-turkey';
        $count = 0;
        return view('massDelete', compact('jv', 'xl', 'count'));
    }

    public function pushDelete(Request $request) {
        $job = Redis::get('job_mass_delete');
        if($job) {
            return redirect(route('massDelete.progress'));
        }

        $request->validate([
            'excel' => 'required|file|mimes:xlsx,csv|max:2048',
        ]);
        if ($request->file('excel')->isValid()) {
            $filePath = $request->file('excel')->store('uploads', 'public');
        }
        if(!isset($request->bases)) {
            return back()->withErrors(['bases' => ['Вы должны выбрать хотя-бы одну базу']]);
        }
        foreach ($request->bases as $base) {
            if (str_starts_with($base, 'jv')) $hasJv = true;
            else $hasXl = true;
            if (isset($hasJv) && isset($hasXl)) break;
        }

        if(isset($hasJv)) $request->session()->now('isJV', true);
        if(isset($hasXl)) $request->session()->now('isXL', true);

        ProductMassDeleteViaExcelJob::dispatch($filePath, $request->bases);
        return redirect()->route('massDelete.progress');
    }

    public function showProgress(Request $request) {
        $isJv = $request->session()->get('isJV');
        $isXl = $request->session()->get('isXL');
        return view('massDeleteProgress', compact('isJv', 'isXl'));
    }
}
