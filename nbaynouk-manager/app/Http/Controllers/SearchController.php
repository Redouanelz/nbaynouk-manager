<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $term = trim($data['q'] ?? '');
        if (mb_strlen($term) < 2) {
            return response()->json(['projects' => [], 'clients' => [], 'businesses' => []]);
        }
        $like = '%'.$term.'%';

        return response()->json([
            'projects' => Project::where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like))->limit(5)->get(['id', 'name', 'code'])->map(fn ($item) => ['label' => $item->name, 'meta' => $item->code, 'url' => route('projects.show', $item)]),
            'clients' => Client::where('name', 'like', $like)->limit(5)->get(['id', 'name'])->map(fn ($item) => ['label' => $item->name, 'meta' => 'Client', 'url' => route('clients.show', $item)]),
            'businesses' => Business::where('name', 'like', $like)->limit(5)->get(['id', 'client_id', 'name'])->map(fn ($item) => ['label' => $item->name, 'meta' => 'Entreprise', 'url' => route('clients.show', $item->client_id)]),
        ]);
    }
}
