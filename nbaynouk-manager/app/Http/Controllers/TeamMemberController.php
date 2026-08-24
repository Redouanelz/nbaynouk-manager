<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamMemberController extends Controller
{
    public function index(): View
    {
        $members = TeamMember::withCount(['projects as active_projects_count' => fn ($q) => $q->active()])->orderBy('name')->get();

        return view('team.index', compact('members'));
    }

    public function show(TeamMember $teamMember): View
    {
        $teamMember->load(['projects' => fn ($q) => $q->with('business.client')->latest()]);

        return view('team.show', compact('teamMember'));
    }

    public function store(Request $request): RedirectResponse
    {
        TeamMember::create($this->validated($request));

        return back()->with('success', 'Membre ajouté.');
    }

    public function update(Request $request, TeamMember $teamMember): RedirectResponse
    {
        $teamMember->update($this->validated($request));

        return back()->with('success', 'Membre mis à jour.');
    }

    public function toggle(TeamMember $teamMember): RedirectResponse
    {
        $teamMember->update(['active' => ! $teamMember->active]);

        return back()->with('success', $teamMember->active ? 'Membre activé.' : 'Membre désactivé.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:255'], 'default_role' => ['nullable', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255']]);
    }
}
