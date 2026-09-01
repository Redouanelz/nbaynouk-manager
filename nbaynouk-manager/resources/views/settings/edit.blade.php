<x-layout title="ParamÃ¨tres">
    <x-page-header eyebrow="Configuration" title="ParamÃ¨tres" description="Informations gÃ©nÃ©rales de lâ€™espace Nbaynouk Manager."/>
    <section class="appearance-card max-w-3xl" aria-labelledby="appearance-title" data-theme-picker data-url="{{ route('settings.appearance.update') }}">
        <p class="eyebrow">Apparence</p>
        <h2 id="appearance-title" class="mt-2">ThÃ¨me de l'interface</h2>
        <p class="mt-2 text-sm text-muted">Choisissez l'apparence de Nbaynouk Manager.</p>
        <div class="theme-options" role="radiogroup" aria-label="ThÃ¨me de l'interface">
            @foreach ($themes as $theme)
                <button type="button" class="theme-option" data-theme-value="{{ $theme->value }}" role="radio" aria-checked="{{ auth()->user()->theme === $theme ? 'true' : 'false' }}">
                    <span class="theme-preview theme-preview-{{ $theme->value }}" aria-hidden="true"><i></i><b></b><em></em></span>
                    <span class="theme-option-copy"><strong>{{ mb_strtoupper($theme->label()) }}</strong><small>{{ $theme === \App\Enums\Theme::Light ? 'ThÃ¨me actuel' : 'Nbaynouk' }}</small></span>
                    <span class="theme-radio" aria-hidden="true"></span>
                </button>
            @endforeach
        </div>
        <p class="theme-status" data-theme-status aria-live="polite"></p>
    </section>
    <form class="form-card mt-6 max-w-3xl" method="POST" action="{{ route('settings.update') }}">@csrf @method('PUT')<div><label class="label" for="agency_name">Nom de lâ€™agence</label><input class="input w-full" id="agency_name" name="agency_name" value="{{ old('agency_name',$agencyName) }}" required></div><div><label class="label" for="currency">Devise par dÃ©faut</label><input class="input w-full" id="currency" name="currency" value="{{ old('currency',$currency) }}" maxlength="3" required><p class="mt-2 text-xs text-muted">Les donnÃ©es existantes restent conservÃ©es dans leur devise dâ€™origine.</p></div><button class="button-primary">Enregistrer les paramÃ¨tres</button></form>
</x-layout>
