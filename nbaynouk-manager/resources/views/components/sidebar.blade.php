<div class="fixed inset-0 z-40 hidden bg-black/40 lg:hidden" data-sidebar-overlay></div>
<aside class="sidebar -translate-x-full lg:translate-x-0" data-sidebar>
    <div>
        <a href="{{ route('dashboard') }}" class="block border-b border-white/10 pb-8">
            <span class="font-serif text-2xl tracking-[.14em]">NBAYNOUK</span>
            <span class="mt-1 block text-[9px] tracking-[.3em] text-stone-400">AGENCY MANAGEMENT</span>
        </a>
        <nav class="mt-9 space-y-8" aria-label="Navigation principale">
            <div><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Vue d’ensemble</a></div>
            <div><p class="nav-label">Projets</p><a class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}">Projets</a><a class="nav-link {{ request()->routeIs('clients.*','businesses.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">Clients</a></div>
            <div><p class="nav-label">Finances</p><a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">Paiements</a><a class="nav-link {{ request()->routeIs('billing.*') ? 'active' : '' }}" href="{{ route('billing.index') }}">Facturation</a></div>
            <div><p class="nav-label">Équipe</p><a class="nav-link {{ request()->routeIs('team.*') ? 'active' : '' }}" href="{{ route('team.index') }}">Équipe</a><a class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}" href="{{ route('services.index') }}">Services</a></div>
        </nav>
    </div>
    <div class="border-t border-white/10 pt-5">
        <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.edit') }}">Paramètres</a>
        <div class="mt-5 flex items-center gap-3 px-3">
            <span class="grid size-9 place-items-center border border-white/20 text-xs">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
            <div class="min-w-0"><p class="truncate text-sm text-stone-100">{{ auth()->user()->name }}</p><p class="text-[10px] uppercase tracking-wider text-stone-500">Administrateur</p></div>
            <form class="ml-auto" method="POST" action="{{ route('logout') }}">@csrf<button class="text-xs text-stone-400 hover:text-white" title="Déconnexion">Quitter</button></form>
        </div>
    </div>
</aside>
