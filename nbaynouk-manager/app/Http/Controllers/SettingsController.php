<?php

namespace App\Http\Controllers;

use App\Enums\Theme;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', ['agencyName' => Setting::valueFor('agency_name', 'Nbaynouk'), 'currency' => Setting::valueFor('currency', 'MAD'), 'themes' => Theme::cases()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['agency_name' => ['required', 'string', 'max:255'], 'currency' => ['required', 'string', 'size:3']]);
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'Paramètres enregistrés.');
    }

    public function updateAppearance(Request $request): JsonResponse
    {
        $data = $request->validate(['theme' => ['required', Rule::enum(Theme::class)]]);
        $request->user()->update(['theme' => $data['theme']]);

        return response()->json(['success' => true, 'theme' => $data['theme']]);
    }
}
