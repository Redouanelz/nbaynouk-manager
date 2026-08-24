<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' — ' : '' }}Nbaynouk Manager</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ivory text-ink antialiased">
    <div class="min-h-screen lg:flex">
        <x-sidebar />
        <div class="min-w-0 flex-1 lg:pl-[272px]">
            <header class="topbar">
                <button class="icon-button lg:hidden" data-sidebar-open aria-label="Ouvrir la navigation">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
                <p class="hidden text-xs uppercase tracking-[.16em] text-muted sm:block">{{ now()->translatedFormat('l j F') }}</p>
                <div class="relative ml-auto w-full max-w-sm" data-global-search data-url="{{ route('search') }}">
                    <label class="sr-only" for="global-search">Recherche globale</label>
                    <input id="global-search" class="search-input" type="search" placeholder="Rechercher..." autocomplete="off">
                    <div class="search-panel hidden" data-search-results role="listbox"></div>
                </div>
                <a class="button-primary hidden sm:inline-flex" href="{{ route('projects.create') }}">Nouveau projet</a>
            </header>
            <main class="page-shell">{{ $slot }}</main>
            <footer class="px-6 pb-8 text-center text-[10px] uppercase tracking-[.18em] text-muted lg:px-12">Nbaynouk Manager — {{ date('Y') }}</footer>
        </div>
    </div>
    <x-flash-message />
</body>
</html>
