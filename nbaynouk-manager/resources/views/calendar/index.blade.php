<x-layout title="Calendrier">
    <div data-calendar data-store-url="{{ route('calendar-events.store') }}" data-update-url="{{ url('/calendar/events') }}" data-visible-start="{{ $days->first()->toDateString() }}" data-visible-end="{{ $days->last()->toDateString() }}">
        <x-page-header eyebrow="Organisation" title="Calendrier" description="Planifiez et suivez les événements de l’agence.">
            <x-slot:actions><button class="button-primary" type="button" data-add-event>+ Ajouter</button></x-slot:actions>
        </x-page-header>

        <section class="calendar-panel">
            <header class="calendar-toolbar">
                <a class="button-secondary calendar-today" href="{{ route('calendar.index', ['month' => $todayMonth]) }}">Aujourd’hui</a>
                <div class="calendar-navigation">
                    <a class="icon-button" href="{{ route('calendar.index', ['month' => $previousMonth]) }}" aria-label="Mois précédent"><svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg></a>
                    <h2>{{ ucfirst($month->translatedFormat('F Y')) }}</h2>
                    <a class="icon-button" href="{{ route('calendar.index', ['month' => $nextMonth]) }}" aria-label="Mois suivant"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></a>
                </div>
            </header>

            <div class="calendar-desktop">
                <div class="calendar-weekdays" aria-hidden="true">@foreach(['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'] as $weekday)<span>{{ $weekday }}</span>@endforeach</div>
                <div class="calendar-grid">
                    @foreach($days as $day)
                        @php($dayEvents = $eventsByDate->get($day->toDateString(), collect()))
                        <section class="calendar-day {{ $day->month !== $month->month ? 'is-outside' : '' }}" data-day="{{ $day->toDateString() }}">
                            <button class="calendar-day-target" type="button" data-add-date="{{ $day->toDateString() }}" aria-label="Ajouter un événement le {{ $day->translatedFormat('j F Y') }}">
                                <time class="{{ $day->isToday() ? 'is-today' : '' }}" datetime="{{ $day->toDateString() }}">{{ $day->day }}</time>
                            </button>
                            <div class="calendar-events" data-events-for="{{ $day->toDateString() }}">
                                @foreach($dayEvents->take(3) as $event)
                                    <button type="button" class="calendar-event {{ $event->color->cssClasses() }}" data-event-id="{{ $event->id }}" data-event-title="{{ $event->title }}" data-event-date="{{ $event->event_date->toDateString() }}" data-event-color="{{ $event->color->value }}" data-event-notes="{{ $event->notes }}"><span>{{ $event->title }}</span></button>
                                @endforeach
                            </div>
                            <button type="button" class="calendar-more {{ $dayEvents->count() <= 3 ? 'hidden' : '' }}" data-show-day="{{ $day->toDateString() }}">+ <span>{{ max(0, $dayEvents->count() - 3) }}</span> autres</button>
                        </section>
                    @endforeach
                </div>
            </div>

            <div class="calendar-mobile">
                @foreach($days->filter(fn($day) => $day->month === $month->month) as $day)
                    @php($dayEvents = $eventsByDate->get($day->toDateString(), collect()))
                    <section class="calendar-mobile-day" data-mobile-day="{{ $day->toDateString() }}">
                        <button type="button" data-add-date="{{ $day->toDateString() }}"><time datetime="{{ $day->toDateString() }}" class="{{ $day->isToday() ? 'is-today' : '' }}"><strong>{{ $day->day }}</strong><span>{{ $day->translatedFormat('D') }}</span></time></button>
                        <div data-mobile-events-for="{{ $day->toDateString() }}">
                            @foreach($dayEvents as $event)<button type="button" class="calendar-event {{ $event->color->cssClasses() }}" data-event-id="{{ $event->id }}" data-event-title="{{ $event->title }}" data-event-date="{{ $event->event_date->toDateString() }}" data-event-color="{{ $event->color->value }}" data-event-notes="{{ $event->notes }}"><span>{{ $event->title }}</span></button>@endforeach
                            <p class="calendar-empty {{ $dayEvents->isNotEmpty() ? 'hidden' : '' }}">Libre</p>
                        </div>
                    </section>
                @endforeach
            </div>
        </section>

        <div class="calendar-modal-root" data-event-modal aria-hidden="true">
            <button class="calendar-modal-overlay" type="button" data-modal-close aria-label="Fermer"></button>
            <section class="calendar-modal" role="dialog" aria-modal="true" aria-labelledby="calendar-modal-title">
                <header><div><p class="eyebrow" data-modal-date-label></p><h2 id="calendar-modal-title">Ajouter au calendrier</h2></div><button type="button" data-modal-close aria-label="Fermer">×</button></header>
                <form data-event-form novalidate>
                    <input type="hidden" name="event_id">
                    <div data-date-field><label class="label" for="calendar-event-date">Date</label><input class="input w-full" id="calendar-event-date" name="event_date" type="date" required><p class="error hidden" data-error="event_date"></p></div>
                    <div><label class="label" for="calendar-event-title">Texte</label><input class="input w-full" id="calendar-event-title" name="title" maxlength="255" required autocomplete="off" placeholder="Ex. Tournage Bayt Al Musk"><p class="error hidden" data-error="title"></p></div>
                    <fieldset><legend class="label">Couleur</legend><div class="calendar-colors">@foreach($colors as $color)<label class="calendar-color-choice {{ $color->cssClasses() }}"><input type="radio" name="color" value="{{ $color->value }}" {{ $loop->first ? 'checked' : '' }}><span></span>{{ $color->label() }}</label>@endforeach</div><p class="error hidden" data-error="color"></p></fieldset>
                    <div><label class="label" for="calendar-event-notes">Notes <span class="font-normal text-muted">(facultatif)</span></label><textarea class="textarea w-full" id="calendar-event-notes" name="notes" maxlength="5000" rows="3"></textarea><p class="error hidden" data-error="notes"></p></div>
                    <p class="alert-error hidden" data-form-error></p>
                    <footer><button class="button-danger mr-auto hidden" type="button" data-delete-event>Supprimer</button><button class="button-secondary" type="button" data-modal-close>Annuler</button><button class="button-primary" type="submit" data-submit-event>Ajouter</button></footer>
                </form>
            </section>
        </div>

        <div class="calendar-modal-root" data-day-modal aria-hidden="true"><button class="calendar-modal-overlay" type="button" data-day-close aria-label="Fermer"></button><section class="calendar-modal calendar-day-modal" role="dialog" aria-modal="true" aria-labelledby="calendar-day-title"><header><div><p class="eyebrow">Tous les événements</p><h2 id="calendar-day-title" data-day-title></h2></div><button type="button" data-day-close aria-label="Fermer">×</button></header><div class="space-y-2" data-day-events></div></section></div>

        <div class="calendar-modal-root" data-delete-modal aria-hidden="true"><button class="calendar-modal-overlay" type="button" data-delete-cancel aria-label="Annuler"></button><section class="calendar-modal calendar-confirm" role="alertdialog" aria-modal="true" aria-labelledby="delete-title"><p class="eyebrow">Confirmation</p><h2 id="delete-title">Supprimer cet événement ?</h2><p class="mt-3 text-sm text-muted"><strong data-delete-title></strong> sera retiré du calendrier.</p><footer><button class="button-secondary" type="button" data-delete-cancel>Annuler</button><button class="button-danger" type="button" data-delete-confirm>Supprimer</button></footer></section></div>
        <div class="toast hidden" role="status" data-calendar-toast><span></span><button type="button" aria-label="Fermer">×</button></div>
    </div>
</x-layout>
