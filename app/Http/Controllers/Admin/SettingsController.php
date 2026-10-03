<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function edit(): View
    {
        return view('admin.settings.edit', ['settings' => $this->settings->all()]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->settings->setMany($request->settings());

        return to_route('admin.settings.edit')->with('success', 'تم حفظ الإعدادات.');
    }
}
