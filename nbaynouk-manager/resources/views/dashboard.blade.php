<x-layout title="Vue d’ensemble">
    <x-page-header eyebrow="Vue d’ensemble" title="Bonsoir, {{ auth()->user()->name }}." description="Voici où en est Nbaynouk aujourd’hui."><x-slot:actions><a class="button-primary" href="{{ route('projects.create') }}">Nouveau projet</a></x-slot:actions></x-page-header>
    <section class="stats-grid">
        <x-stat label="Projets actifs" :value="$activeCount" :note="'+'.$createdThisMonth.' ce mois'" />
        <x-stat label="Valeur mensuelle" :value="\App\Support\Money::format($monthlyValue)" />
        <x-stat label="Encaissé ce mois" :value="\App\Support\Money::format($receivedThisMonth)" />
        <x-stat label="À encaisser" :value="\App\Support\Money::format($outstanding)" :note="$pendingCount.' paiement(s) en attente'" />
    </section>
    <div class="mt-14 grid gap-12 xl:grid-cols-[1.4fr_.6fr]">
        <section><div class="section-heading"><div><p class="eyebrow">Priorités</p><h2>À surveiller</h2></div><a href="{{ route('billing.index') }}">Voir tout</a></div><div class="divide-y divide-border border-y border-border">
            @forelse($watchPeriods as $period)<a class="watch-row" href="{{ route('projects.show',$period->project) }}"><div><p class="font-medium">{{ $period->project->business->name }}</p><p class="mt-1 text-xs text-muted">{{ $period->payment_status->value === 'overdue' ? 'Paiement en retard' : 'Échéance prochaine' }}</p></div><div class="text-right"><p>{{ \App\Support\Money::format($period->remaining_amount) }}</p><p class="mt-1 text-xs text-muted">{{ $period->due_date?->diffForHumans() }}</p></div></a>@empty<p class="py-8 text-sm text-muted">Aucune échéance urgente.</p>@endforelse
            @foreach($waitingProjects as $project)<a class="watch-row" href="{{ route('projects.show',$project) }}"><div><p class="font-medium">{{ $project->business->name }}</p><p class="mt-1 text-xs text-muted">Projet en attente</p></div><x-badge :value="$project->status" /></a>@endforeach
        </div></section>
        <section><div class="section-heading"><div><p class="eyebrow">Portefeuille</p><h2>Répartition</h2></div></div><div class="space-y-1">@foreach(\App\Enums\ProjectStatus::cases() as $status)@if(($statusCounts[$status->value] ?? 0)>0)<div class="flex items-center justify-between border-b border-border py-3 text-sm"><span>{{ $status->label() }}</span><span class="font-serif text-xl">{{ $statusCounts[$status->value] }}</span></div>@endif @endforeach</div></section>
    </div>
    <section class="mt-16"><div class="section-heading"><div><p class="eyebrow">Derniers dossiers</p><h2>Projets récents</h2></div><a href="{{ route('projects.index') }}">Tous les projets</a></div>
        <div class="table-wrap"><table><thead><tr><th>Projet</th><th>Client</th><th>Statut</th><th>Montant</th><th>Payé</th><th>Reste</th><th>Échéance</th></tr></thead><tbody>@forelse($recentProjects as $project)<tr class="clickable-row" data-href="{{ route('projects.show',$project) }}"><td><strong>{{ $project->business->name }}</strong><span>{{ $project->code }}</span></td><td>{{ $project->business->client->name }}</td><td><x-badge :value="$project->status" /></td><td>{{ \App\Support\Money::format($project->amount) }}</td><td>{{ \App\Support\Money::format($project->total_paid) }}</td><td>{{ \App\Support\Money::format($project->remaining_amount) }}</td><td>{{ $project->next_payment_date?->translatedFormat('d M') ?? '—' }}</td></tr>@empty<tr><td colspan="7">Aucun projet récent.</td></tr>@endforelse</tbody></table></div>
    </section>
</x-layout>
