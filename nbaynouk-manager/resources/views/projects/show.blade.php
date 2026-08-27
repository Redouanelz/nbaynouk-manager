<x-layout :title="$project->name">
    <p class="breadcrumb"><a href="{{ route('projects.index') }}">Projets</a> / {{ $project->name }}</p>
    <div class="page-header">
        <div>
            <p class="eyebrow">{{ $project->code }}</p>
            <h1 class="page-title">{{ $project->name }}</h1>
            <p class="mt-2 text-sm text-muted">{{ $project->business->name }} · {{ $project->business->client->name }} ·
                {{ $project->billing_type->label() }}</p>
        </div>
        <div class="flex gap-2"><x-badge :value="$project->status" /><a class="button-secondary"
                href="{{ route('projects.edit', $project) }}">Modifier</a></div>
    </div>
    <section class="stats-grid mt-8"><x-stat label="Valeur" :value="\App\Support\Money::format($project->amount)" /><x-stat label="Payé"
            :value="\App\Support\Money::format($project->total_paid)" /><x-stat label="Reste" :value="\App\Support\Money::format($project->remaining_amount)" /><x-stat label="Avancement" :value="$project->progress_percentage . '%'"
            :note="$project->completed_services_count .
                ' / ' .
                $project->total_services_count .
                ' services terminés'" /></section>
    <nav class="tabs"><a href="#overview">Vue d’ensemble</a><a href="#progress">Avancement</a><a
            href="#billing">Facturation</a><a href="#payments">Paiements</a><a href="#team">Équipe</a><a
            href="#activity">Activité</a><a href="#notes">Notes</a></nav>
    <section id="overview" class="detail-grid">
        <article class="panel">
            <p class="eyebrow">Client</p>
            <h2>{{ $project->business->client->name }}</h2>
            <dl>
                <div>
                    <dt>Téléphone</dt>
                    <dd>{{ $project->business->client->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt>E-mail</dt>
                    <dd>{{ $project->business->client->email ?? '—' }}</dd>
                </div>
            </dl>
        </article>
        <article class="panel">
            <p class="eyebrow">Entreprise</p>
            <h2>{{ $project->business->name }}</h2>
            <dl>
                <div>
                    <dt>Site web</dt>
                    <dd>{{ $project->business->website ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Instagram</dt>
                    <dd>{{ $project->business->instagram ?? '—' }}</dd>
                </div>
            </dl>
        </article>
        <article class="panel">
            <p class="eyebrow">Calendrier</p>
            <h2>{{ $project->status->label() }}</h2>
            <dl>
                <div>
                    <dt>Début</dt>
                    <dd>{{ $project->start_date?->translatedFormat('d F Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Fin</dt>
                    <dd>{{ $project->end_date?->translatedFormat('d F Y') ?? '—' }}</dd>
                </div>
            </dl>
        </article>
    </section>
    <section id="progress" class="section-block" data-project-progress>
        <div class="progress-summary">
            <div>
                <p class="eyebrow">Avancement du projet</p>
                <p class="progress-number" data-progress-value>{{ $project->progress_percentage }}%</p>
            </div>
            <div class="progress-summary-track"><x-progress-bar :value="$project->progress_percentage" data-progress-bar />
                <p data-progress-count>{{ $project->completed_services_count }} services terminés sur
                    {{ $project->total_services_count }}</p>
            </div>
        </div>
        <div class="service-list">
            @forelse($project->activeProjectServices as $projectService)
                @php($completed = $projectService->status === \App\Enums\ProjectServiceStatus::Completed)
                <article class="service-card {{ $completed ? 'is-completed' : '' }}" data-service-card>
                    <button type="button" class="service-toggle" data-service-toggle
                        data-url="{{ route('project-services.toggle', [$project, $projectService]) }}"
                        aria-label="Marquer {{ $projectService->service->name }} comme {{ $completed ? 'à faire' : 'terminé' }}"
                        aria-pressed="{{ $completed ? 'true' : 'false' }}"><span
                            data-toggle-icon>{{ $completed ? '✓' : '○' }}</span></button>
                    <button type="button" class="service-details"
                        data-service-open><span>{{ $projectService->service->name }}<small
                                data-service-status>{{ $projectService->status->label() }}</small><em
                                data-service-date>{{ $completed ? 'Terminé le ' . $projectService->completed_at?->translatedFormat('d F Y') : 'Ajouter les détails et documents nécessaires.' }}</em></span><b>→</b></button>
                    <template data-service-template>
                        <div data-drawer-data data-service-name="{{ $projectService->service->name }}"
                            data-service-status="{{ $projectService->status->label() }}"
                            data-update-url="{{ route('project-services.update', [$project, $projectService]) }}"
                            data-upload-url="{{ route('project-service-attachments.store', [$project, $projectService]) }}"
                            data-created="{{ $projectService->created_at->translatedFormat('d F Y') }}"
                            data-completed="{{ $projectService->completed_at?->translatedFormat('d F Y') ?? '—' }}">
                            <textarea data-notes>{{ $projectService->notes }}</textarea>
                            <div data-attachments>
                                @foreach ($projectService->attachments as $attachment)
                                    @php($attachmentUrl = route('project-service-attachments.show', [$project, $projectService, $attachment]))
                                    <article class="attachment-card" data-attachment><a href="{{ $attachmentUrl }}"
                                            target="_blank" rel="noopener">
                                            @if (str_starts_with($attachment->mime_type ?? '', 'image/'))
                                            <img src="{{ $attachmentUrl }}" alt="">@else<span>PDF</span>
                                            @endif
                                            <strong>
                                                {{ $attachment->original_name }}</strong>
                                        </a><button type="button" data-attachment-delete
                                            data-url="{{ route('project-service-attachments.destroy', [$project, $projectService, $attachment]) }}">Supprimer</button>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </template>
            </article>@empty <x-empty-state title="Aucun service"
                    description="Ajoutez des services au projet pour commencer le suivi." />
            @endforelse
        </div>
        <form class="custom-service-form" data-custom-service-form
            action="{{ route('project-services.custom.store', $project) }}"><label class="label"
                for="custom-service-name">Ajouter un service personnalisé</label>
            <div><input class="input" id="custom-service-name" name="name" maxlength="255" required
                    placeholder="Ex. Validation client"><button class="button-secondary">Ajouter</button></div>
        </form>
    </section>
    <section id="billing" class="section-block">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Finances</p>
                <h2>Facturation</h2>
            </div>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('billing.next', $project) }}">@csrf<button
                        class="button-secondary">Générer le mois suivant</button></form><a class="button-primary"
                    href="{{ route('billing.create', $project) }}">Nouvelle période</a>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Période</th>
                        <th>Montant</th>
                        <th>Payé</th>
                        <th>Reste</th>
                        <th>Échéance</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->billingPeriods->sortByDesc('period_start') as $period)
                        <tr>
                            <td>{{ $period->period_start?->translatedFormat('M Y') ?? $period->description }}</td>
                            <td>{{ \App\Support\Money::format($period->amount) }}</td>
                            <td>{{ \App\Support\Money::format($period->total_paid) }}</td>
                            <td>{{ \App\Support\Money::format($period->remaining_amount) }}</td>
                            <td>{{ $period->due_date?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td><x-badge :value="$period->payment_status" /></td>
                    </tr>@empty<tr>
                            <td colspan="6">Aucune période.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section id="payments" class="section-block">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Encaissements</p>
                <h2>Paiements</h2>
            </div><a class="button-primary"
                href="{{ route('payments.create', ['project_id' => $project->id]) }}">Enregistrer un paiement</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Méthode</th>
                        <th>Référence</th>
                        <th>Montant</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->payments->sortByDesc('payment_date') as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->translatedFormat('d F Y') }}</td>
                            <td>{{ $payment->method?->label() ?? '—' }}</td>
                            <td>{{ $payment->reference ?? '—' }}</td>
                            <td>{{ \App\Support\Money::format($payment->amount) }}</td>
                            <td>
                                <form method="POST" action="{{ route('payments.destroy', $payment) }}"
                                    data-confirm="Supprimer ce paiement ?">@csrf @method('DELETE')<button
                                        class="text-link text-danger">Supprimer</button></form>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="5">Aucun paiement enregistré.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section id="team" class="section-block">
        <div class="section-heading">
            <h2>Équipe</h2>
        </div>
        <div class="grid gap-3 lg:grid-cols-2">
            @forelse($project->teamMembers as $member)
                <div class="flex justify-between border-b border-border pb-3"><span>{{ $member->name }}</span><span
                        class="text-sm text-muted">{{ $member->pivot->role ?? $member->default_role }}</span></div>
            @empty<p class="text-sm text-muted">Aucun membre.</p>
            @endforelse
        </div>
    </section>
    <section id="activity" class="section-block">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Journal</p>
                <h2>Activité</h2>
            </div>
        </div>
        <div class="timeline">
            @forelse($project->activityLogs as $log)
                <article><time>{{ $log->occurred_at->translatedFormat('d F Y — H:i') }}</time>
                    <p>{{ $log->description }}</p>
            </article>@empty<p class="text-sm text-muted">Aucune activité.</p>
            @endforelse
        </div>
    </section>
    <section id="notes" class="section-block">
        <div class="section-heading">
            <h2>Notes internes</h2>
        </div>
        <form method="POST" action="{{ route('projects.notes', $project) }}">@csrf @method('PATCH')
            <textarea class="textarea w-full" rows="6" name="notes">{{ $project->notes }}</textarea><button class="button-primary mt-3">Enregistrer les notes</button>
        </form>
    </section>
    <section class="mt-16 border-t border-border pt-8">
        <form method="POST" action="{{ route('projects.destroy', $project) }}"
            data-confirm="Archiver ce projet ? Ses données seront conservées.">@csrf @method('DELETE')<button
                class="button-danger">Archiver le projet</button></form>
    </section>
    <div class="service-drawer-root" data-service-drawer aria-hidden="true"><button class="drawer-overlay"
            type="button" data-drawer-close aria-label="Fermer"></button>
        <aside class="service-drawer" role="dialog" aria-modal="true" aria-labelledby="drawer-title">
            <header>
                <div>
                    <p class="eyebrow">Service</p>
                    <h2 id="drawer-title" data-drawer-title></h2><span class="drawer-status"
                        data-drawer-status></span>
                </div><button type="button" data-drawer-close aria-label="Fermer">×</button>
            </header>
            <form data-drawer-form><label class="label" for="drawer-notes">Note interne</label>
                <textarea class="textarea w-full" rows="6" id="drawer-notes" name="notes" data-drawer-notes></textarea>
                <div class="drawer-attachments">
                    <p class="eyebrow">Pièces jointes</p>
                    <div data-drawer-attachments></div><label class="file-picker">+ Ajouter une image ou un
                        fichier<input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                            data-drawer-files></label><small>JPG, PNG, WEBP ou PDF · 10 Mo maximum par fichier</small>
                </div>
                <dl class="drawer-dates">
                    <div>
                        <dt>Créé le</dt>
                        <dd data-drawer-created></dd>
                    </div>
                    <div>
                        <dt>Terminé le</dt>
                        <dd data-drawer-completed></dd>
                    </div>
                </dl>
                <footer><button type="button" class="button-secondary" data-drawer-close>Annuler</button><button
                        class="button-primary">Enregistrer</button></footer>
            </form>
        </aside>
    </div>
</x-layout>
