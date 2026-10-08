<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ app()->bound('currentOrganization') ? app('currentOrganization')->themePalette()['primary'] : \App\Support\OrganizationThemes::palette(\App\Support\OrganizationThemes::DEFAULT)['primary'] }}">
    <title>@yield('title', 'SuperSkyCrew')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.platform-styles')
    @if(app()->bound('currentOrganization'))
        @include('partials.organization-theme', ['organization' => app('currentOrganization')])
    @endif
    @stack('styles')
</head>
<body>
@include('partials.impersonation-banner')
@php
    $organization = app('currentOrganization');
    $membership = app()->bound('currentOrganizationUser') ? app('currentOrganizationUser') : null;
    $rbac = app(\App\Services\OrganizationRbacService::class);
    $userId = (int) auth()->id();
    $navEmail = auth()->user()?->email ?? '';
    $canPeople = $rbac->can($organization->id, $userId, 'people.access');
    $canTime = $rbac->can($organization->id, $userId, 'time.access') || $rbac->can($organization->id, $userId, 'payroll.export');
    $canApprove = $rbac->can($organization->id, $userId, 'requests.approve');
    $canTeam = $rbac->can($organization->id, $userId, 'team.manage');
    $canSettings = $rbac->canOpenSettings($organization->id, $userId);
    $isOwner = $rbac->isOwner($organization->id, $userId);
    $isWorker = $membership?->role === \App\Enums\OrganizationRole::Employee;
    $workspaceCount = \App\Support\UserOrganizationNavigation::organizationUsers($userId)
        ->filter(fn ($entry) => $entry->organization?->status === \App\Enums\OrganizationStatus::Active)
        ->count();
    $settingsUrl = fn (?string $tab = null, ?string $section = null) => route('organization.settings.index', array_filter([
        'slug' => $organization->slug,
        'tab' => $tab,
        'section' => $section,
    ]));
    $ustrojUrl = fn (?string $katalog = 'poslovna') => route('organization.settings.index', array_filter([
        'slug' => $organization->slug,
        'tab' => 'organizacija',
        'section' => 'ustroj',
        'katalog' => $katalog,
    ]));
@endphp
<div class="app-shell" id="appShell">
    <div class="app-sidebar-backdrop" id="appSidebarBackdrop"></div>
    <aside class="app-sidebar" id="appSidebar">
        <a class="app-sidebar-brand" href="{{ route('organization.dashboard', $organization->slug) }}">
            <span class="app-sidebar-lockup" id="appSidebarBrandLogo"
                  style="--lockup: url('{{ \App\Support\OrganizationThemes::productLogoUrl($organization->theme_key, true) }}')">
                <span class="app-sidebar-mark" aria-hidden="true"></span>
                <span class="app-sidebar-word" aria-hidden="true"></span>
                <span class="visually-hidden">SuperSkyCrew</span>
            </span>
        </a>
        @include('partials.organization-sidebar')
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <button type="button"
                        class="navbar-hamburger-btn"
                        id="navModulesMenuBtn"
                        aria-expanded="false"
                        aria-controls="appSidebar"
                        title="Izbornik">
                    <span class="navbar-hamburger-icon" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span class="visually-hidden">Izbornik</span>
                </button>
                <p class="app-topbar-title">
                    <span class="app-topbar-title-prefix">{{ $organization->navbarBrandPrefix() }}</span>
                </p>
            </div>
            <div class="app-topbar-user">
                <div class="d-flex align-items-center gap-2" title="{{ $navEmail }}">
                    <div class="navbar-modules-user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($navEmail, 0, 1)) }}</div>
                    <div class="d-none d-md-block min-w-0">
                        <div class="navbar-user-bar-label">Prijavljeni korisnik</div>
                        <div class="navbar-user-bar-email text-truncate">{{ $navEmail }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mb-0">@csrf
                    <button type="submit" class="btn btn-navbar-logout">Odjava</button>
                </form>
            </div>
        </header>
        @include('partials.trial-notice')

        <main class="pt-3 pb-5">
            <div class="container-fluid px-4">
                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    document.querySelectorAll('.app-sidebar-group-toggle').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var group = btn.closest('.app-sidebar-group');
            if (!group) return;
            var open = group.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
    var shell = document.getElementById('appShell');
    var toggle = document.getElementById('navModulesMenuBtn');
    var backdrop = document.getElementById('appSidebarBackdrop');
    function setOpen(open) {
        if (!shell || !toggle) return;
        shell.classList.toggle('sidebar-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (backdrop) backdrop.hidden = !open;
    }
    if (toggle) {
        toggle.addEventListener('click', function () {
            setOpen(!shell.classList.contains('sidebar-open'));
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () { setOpen(false); });
    }
})();
</script>
@stack('scripts')
</body>
</html>
