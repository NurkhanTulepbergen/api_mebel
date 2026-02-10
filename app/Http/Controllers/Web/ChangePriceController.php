<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ChangePrice\MainJob;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * Handles UI-driven price change workflow.
 */
class ChangePriceController extends Controller
{
    /**
     * Render change price form with grouped domain lists.
     */
    public function display()
    {
        $domains = Domain::getSeparatedDomains();
        return view('changePrice.form', compact('domains'));
    }

    /**
     * Validate request, enqueue change price job, and redirect to progress page.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function push(Request $request)
    {
        $jobStatus = Redis::get('change_price_job:status');
        if ($jobStatus === 'started')
            return redirect()->route('changePrice.progress');

        $userId = auth()->id();
        $request->validate( [
            'baseIds' => ['required', 'array', 'min:1'],
            'baseIds.*' => ['integer', 'exists:domains,id'],
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
            $filename = "change_price_{$userId}.{$ext}";
            $filePath = $file->storeAs("change_price", $filename, 'public');
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
        MainJob::dispatch($filePath, $eans, $request->baseIds, $userId);
        return redirect()->route('changePrice.progress');
    }

    /**
     * Show progress screen for the currently running job.
     */
    public function progress()
    {
        return view('changePrice.progress');
    }
}
