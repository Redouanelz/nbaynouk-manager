<x-layout title="Charges">
    <x-page-header eyebrow="Finances" title="Charges" description="Suivez les coûts et la rentabilité de chaque projet." />

    <section class="expense-overview-kpis">
        <x-stat label="Total des charges" :value="\App\Support\Money::format($kpis['total'])" />
        <x-stat label="Charges payées" :value="\App\Support\Money::format($kpis['paid'])" />
        <x-stat label="À payer" :value="\App\Support\Money::format($kpis['pending'])" />
        <x-stat label="Marge estimée totale" :value="\App\Support\Money::format($kpis['profit'])" />
    </section>

    <form class="filter-bar mt-8" method="GET">
        <input class="input min-w-52 flex-1" type="search" name="search" value="{{ request('search') }}" placeholder="Rechercher un projet…">
        <select class="select" name="status"><option value="">Tous les statuts</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
        <select class="select" name="profitability"><option value="">Toute rentabilité</option><option value="high" @selected(request('profitability') === 'high')>Très rentable</option><option value="medium" @selected(request('profitability') === 'medium')>Rentable</option><option value="low" @selected(request('profitability') === 'low')>Faible</option></select>
        <select class="select" name="sort"><option value="">Tri par défaut</option><option value="project" @selected(request('sort') === 'project')>Projet</option><option value="expenses" @selected(request('sort') === 'expenses')>Charges</option><option value="profit" @selected(request('sort') === 'profit')>Marge</option><option value="profitability" @selected(request('sort') === 'profitability')>Rentabilité</option></select>
        <select class="select" name="direction"><option value="desc" @selected(request('direction', 'desc') === 'desc')>Décroissant</option><option value="asc" @selected(request('direction') === 'asc')>Croissant</option></select>
        <button class="button-secondary">Filtrer</button>
    </form>

    <div class="expense-project-list mt-8">
        @forelse($projects as $project)
            @php($margin = $project->overview_margin === null ? null : round((float) $project->overview_margin, 1))
            <article class="expense-project-card profitability-{{ $project->profitabilityLevel($margin) ?? 'none' }}">
                <div class="expense-project-identity"><p class="eyebrow">{{ $project->code }}</p><h2>{{ $project->name }}</h2><p>{{ $project->business->name }} · {{ $project->business->client->name }}</p>@if($project->billing_type === \App\Enums\BillingType::Monthly)<span>Mois en cours · {{ now()->translatedFormat('F Y') }}</span>@else<span>{{ $project->billing_type->label() }}</span>@endif</div>
                <dl><div><dt>Valeur {{ $project->billing_type === \App\Enums\BillingType::Monthly ? 'mensuelle' : 'du projet' }}</dt><dd>{{ \App\Support\Money::format($project->amount) }}</dd></div><div><dt>Charges</dt><dd>{{ \App\Support\Money::format($project->overview_expenses) }}</dd></div><div class="is-profit"><dt>Marge estimée</dt><dd>{{ \App\Support\Money::format($project->overview_profit) }}</dd></div><div><dt>Rentabilité</dt><dd>{{ $margin === null ? '—' : number_format($margin, 1, ',', ' ').' %' }}</dd></div><div><dt>À payer</dt><dd>{{ \App\Support\Money::format($project->overview_pending) }}</dd></div></dl>
                <div class="expense-project-action"><span>{{ $project->overview_expenses_count }} charge{{ $project->overview_expenses_count > 1 ? 's' : '' }}</span><a href="{{ route('projects.show', $project) }}#expenses">Voir les charges →</a></div>
            </article>
        @empty
            <x-empty-state title="Aucune charge enregistrée pour le moment." description="Ajoutez des charges depuis un projet pour commencer à suivre sa rentabilité."><x-slot:action><a class="button-primary" href="{{ route('projects.index') }}">Voir les projets</a></x-slot:action></x-empty-state>
        @endforelse
    </div>
    <div class="mt-8">{{ $projects->links() }}</div>
</x-layout>
