<div class="fixed inset-0 z-40 hidden bg-slate-900/30 lg:hidden" data-sidebar-overlay></div>
<aside class="sidebar -translate-x-full lg:translate-x-0" data-sidebar>
    <div>
        <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark"><img src="{{ asset('images/nbaynouk-logo.png') }}" alt="" width="40" height="40"></span><span><span class="brand-name">NBAYNOUK</span><span class="brand-subtitle">Agency management</span></span></a>
        <nav aria-label="Navigation principale">
            <div><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><svg viewBox="0 0 24 24"><path d="M4 13h6V4H4v9Zm0 7h6v-4H4v4Zm10 0h6v-9h-6v9Zm0-16v4h6V4h-6Z"/></svg>Dashboard</a></div>
            <div><p class="nav-label">Gestion</p>
                <a class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}"><svg viewBox="0 0 24 24"><path d="M4 7.5h6l2 2h8v10H4v-12Z"/></svg>Projets</a>
                <a class="nav-link {{ request()->routeIs('clients.*','businesses.*') ? 'active' : '' }}" href="{{ route('clients.index') }}"><svg viewBox="0 0 24 24"><path d="M16 20v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM17 11l2 2 3-4"/></svg>Clients</a>
                <a class="nav-link {{ request()->routeIs('team.*') ? 'active' : '' }}" href="{{ route('team.index') }}"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>Équipe</a>
                <a class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}" href="{{ route('services.index') }}"><svg viewBox="0 0 24 24"><path d="m12 3 8 4.5-8 4.5-8-4.5L12 3ZM4 12l8 4.5 8-4.5M4 16.5l8 4.5 8-4.5"/></svg>Services</a>
            </div>
            <div><p class="nav-label">Finance</p>
                <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}"><svg viewBox="0 0 24 24"><path d="M3 6h18v12H3V6Zm0 4h18M7 15h3"/></svg>Paiements</a>
                <a class="nav-link {{ request()->routeIs('billing.*') ? 'active' : '' }}" href="{{ route('billing.index') }}"><svg viewBox="0 0 24 24"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm3 5h6M9 12h6"/></svg>Facturation</a>
            </div>
        </nav>
    </div>
    <div class="sidebar-user"><p class="nav-label">Autre</p><a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.edit') }}"><svg viewBox="0 0 24 24"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V21h-4v-.09A1.7 1.7 0 0 0 9 19.36a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.63 15 1.7 1.7 0 0 0 3.08 14H3v-4h.09A1.7 1.7 0 0 0 4.64 9a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.63 1.7 1.7 0 0 0 10 3.08V3h4v.09A1.7 1.7 0 0 0 15 4.64a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.37 9 1.7 1.7 0 0 0 20.92 10H21v4h-.09A1.7 1.7 0 0 0 19.4 15Z"/></svg>Paramètres</a>
        <div class="sidebar-user-card"><span class="sidebar-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span><div class="min-w-0"><p class="truncate text-sm font-bold">{{ auth()->user()->name }}</p><p class="text-[11px] font-semibold text-muted">Administrateur</p></div><form class="ml-auto" method="POST" action="{{ route('logout') }}">@csrf<button class="text-xs font-bold text-muted hover:text-danger" title="Déconnexion">Quitter</button></form></div>
    </div>
</aside>
