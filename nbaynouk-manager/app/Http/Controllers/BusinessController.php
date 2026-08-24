<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessRequest;
use App\Models\Business;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function create(Client $client): View
    {
        return view('businesses.form', compact('client'));
    }

    public function store(BusinessRequest $request): RedirectResponse
    {
        $business = Business::create($request->validated());

        return redirect()->route('clients.show', $business->client_id)->with('success', 'Entreprise ajoutée.');
    }

    public function edit(Business $business): View
    {
        $client = $business->client;

        return view('businesses.form', compact('business', 'client'));
    }

    public function update(BusinessRequest $request, Business $business): RedirectResponse
    {
        $business->update($request->validated());

        return redirect()->route('clients.show', $business->client_id)->with('success', 'Entreprise mise à jour.');
    }
}
