<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Http\Traits\TampermonkeyTrait;

class ScriptCopyWidget extends Widget
{
    use TampermonkeyTrait;

    protected static string $view = 'filament.widgets.script-copy-widget';

    public string $script = '';

    public function copyScriptToClipboard(): void
    {
        // генерируем скрипт и открываем модалку по его id
        $this->script = $this->createScript();
        $this->dispatch('open-modal', id: 'tm-script-modal'); // ключевой момент
    }
}
