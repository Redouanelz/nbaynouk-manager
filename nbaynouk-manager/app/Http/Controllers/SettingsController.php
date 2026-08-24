<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', ['agencyName' => Setting::valueFor('agency_name', 'Nbaynouk'), 'currency' => Setting::valueFor('currency', 'MAD')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['agency_name' => ['required', 'string', 'max:255'], 'currency' => ['required', 'string', 'size:3']]);
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

return back()->with('success', 'Paramètres enregistrés.');
    }
}
