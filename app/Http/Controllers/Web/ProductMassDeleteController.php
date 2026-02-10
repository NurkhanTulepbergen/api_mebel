<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Jobs\MassDelete\MainJob;
use Illuminate\Support\Facades\Redis;
use App\Models\Domain;

/**
 * Handles UI-driven mass product deletion workflow.
 */
class ProductMassDeleteController extends Controller
{
    /**
     * Render mass delete form with selectable domains.
     */
    public function massDelete() {
        $domains = Domain::getSeparatedDomains();
        return view('massDelete.form', compact('domains'));
    }

    /**
     * Validate request, enqueue mass delete job, and redirect to progress page.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function pushDelete(Request $request) {
        $userId = auth()->id();
        $job = Redis::get('job_mass_delete');
        if($job)
            return redirect(route('massDelete.progress'));

        $request->validate( [
            'base_ids' => ['required', 'array', 'min:1'],
            'base_ids.*' => ['integer', 'exists:domains,id'],
            'mode' => ['required', 'in:inputs,file'],
            'excel' => [$request->mode === 'file' ? 'required' : 'nullable', 'file', 'mimes:xlsx,csv'],
            'eans' => [$request->mode === 'inputs' ? 'required' : 'nullable', 'array', 'max:100'],
            'eans.*' => [$request->mode === 'inputs' ? 'string' : 'nullable'],
        ]);

        $filePath = null;
        $eans = [];

        if ($request->mode === 'file') {
            $file = $request->file('excel');

            if (!$file || !$file->isValid())
                return back()->withErrors(['excel' => 'Файл повреждён или не был загружен корректно.']);

            $ext = $file->getClientOriginalExtension();
            $filename = "mass_delete_{$userId}.{$ext}";
            $filePath = $file->storeAs("mass_delete", $filename, 'public');
        } else {
            // inputs: очистка, trim, удаление пустых, уникальность
            $eans = collect($request->input('eans', []))
                ->map(function ($v) { return is_scalar($v) ? trim($v) : $v; })
                ->filter(function ($v) { return (string)$v !== ''; })
                ->unique()
                ->values()
                ->all();

            if (empty($eans))
                return back()->withErrors(['eans' => 'Вы должны указать хотя бы одно значение EAN.']);
        }

        MainJob::dispatch($filePath, $eans, $request->base_ids, $userId);
        return redirect()->route('massDelete.progress');
    }

    /**
     * Show progress screen for the currently running job.
     */
    public function showProgress() {
        $job = Redis::get('job_mass_delete');
        // if(!$job)
        //     return redirect(route('massDelete'));

        return view('massDelete.progress');
    }
}
