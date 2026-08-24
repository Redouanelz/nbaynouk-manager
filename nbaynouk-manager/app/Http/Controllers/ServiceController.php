<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $services = Service::withCount('projects')->orderBy('name')->get();

        return view('services.index', compact('services'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:services,name']]);
        Service::create(['name' => $data['name'], 'slug' => Str::slug($data['name'])]);

        return back()->with('success', 'Service ajouté.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('services')->ignore($service)]]);
        $service->update(['name' => $data['name'], 'slug' => Str::slug($data['name'])]);

        return back()->with('success', 'Service mis à jour.');
    }
}
