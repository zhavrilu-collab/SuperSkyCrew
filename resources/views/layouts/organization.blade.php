<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ app()->bound('currentOrganization') ? app('currentOrganization')->themePalette()['primary'] : '#1b431c' }}">
    <title>@yield('title', 'HR SaaS')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.platform-styles')
    @if(app()->bound('currentOrganization'))
        @include('partials.organization-theme', ['organization' => app('currentOrganization')])
    @endif
    @stack('styles')
</head>
<body>
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
    $navModule = trim((string) $__env->yieldContent('nav-suffix'));
    if ($navModule === '') {
        $navModule = match (true) {
            request()->routeIs('organization.landing') => 'Moduli',
            request()->routeIs('organization.dashboard') => 'Pregled',
            request()->routeIs('organization.people.*', 'organization.expiries.*', 'organization.handovers.*', 'organization.structure.*') => 'Kadrovi',
            request()->routeIs('organization.timesheet.*', 'organization.schedule.*', 'organization.exceptions.*') => 'Vrijeme',
            request()->routeIs('organization.clock', 'organization.requests.*', 'organization.absences.*') => 'Moje',
            request()->routeIs('organization.approvals.*') => 'Odobrenja',
            request()->routeIs('organization.settings.*', 'organization.team.*') => 'Postavke',
            default => null,
        };
    }
    $settingsUrl = fn (?string $tab = null, ?string $section = null) => route('organization.settings.index', array_filter([
        'slug' => $organization->slug,
        'tab' => $tab,
        'section' => $section,
    ]));
@endphp
<header class="app-header">
<nav class="navbar navbar-dark bg-siletici shadow-sm">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2 min-w-0">
            @if($organization->logoUrl())
                <img src="{{ $organization->logoUrl() }}" class="navbar-brand-logo" alt="">
            @endif
            <a class="navbar-brand navbar-brand-org text-white mb-0 text-truncate"
               href="{{ route('organization.landing', $organization->slug) }}">
                <span class="navbar-brand-prefix">{{ $organization->navbarBrandPrefix() }}</span>
                @if($navModule)
                    <span class="navbar-brand-suffix"> - {{ $navModule }}</span>
                @endif
            </a>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <div class="navbar-user-bar d-flex align-items-center gap-2">
                <div class="navbar-user-bar-card" title="{{ $navEmail }}">
                    <div class="navbar-modules-user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($navEmail, 0, 1)) }}</div>
                    <div class="navbar-user-bar-meta min-w-0 d-none d-lg-block">
                        <div class="navbar-user-bar-label">Prijavljeni korisnik</div>
                        <div class="navbar-user-bar-email text-truncate">{{ $navEmail }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mb-0">@csrf
                    <button type="submit" class="btn btn-navbar-logout btn-navbar-logout--header">Odjava</button>
                </form>
            </div>
            <div class="dropdown">
                <button type="button"
                        class="navbar-hamburger-btn"
                        id="navModulesMenuBtn"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside"
                        aria-expanded="false"
                        title="Izbornik">
                    <span class="navbar-hamburger-icon" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span class="visually-hidden">Izbornik</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end navbar-modules-menu shadow" aria-labelledby="navModulesMenuBtn">
                    <li class="navbar-modules-menu-header">Navigacija</li>
                    <li>
                        <a class="dropdown-item @if(request()->routeIs('organization.landing')) active @endif"
                           href="{{ route('organization.landing', $organization->slug) }}">Moduli</a>
                    </li>
                    <li>
                        <a class="dropdown-item @if(request()->routeIs('organization.dashboard')) active @endif"
                           href="{{ route('organization.dashboard', $organization->slug) }}">Pregled</a>
                    </li>

                    @if($canPeople && ! $isWorker)
                    @php $kadarOpen = request()->routeIs('organization.people.*', 'organization.expiries.*', 'organization.handovers.*', 'organization.structure.*') || (request()->routeIs('organization.settings.*') && request('tab') === 'kadar'); @endphp
                    <li class="navbar-modules-group @if($kadarOpen) is-open @endif">
                        <div class="navbar-modules-group-head">
                            <a class="dropdown-item navbar-modules-group-link @if($kadarOpen) active @endif"
                               href="{{ route('organization.people.index', $organization->slug) }}">Kadrovi</a>
                            <button type="button" class="navbar-modules-group-toggle" aria-expanded="{{ $kadarOpen ? 'true' : 'false' }}">
                                <span class="navbar-modules-chevron"></span>
                            </button>
                        </div>
                        <ul class="navbar-modules-submenu list-unstyled mb-0">
                            <li><a class="dropdown-item" href="{{ route('organization.people.index', $organization->slug) }}">Evidencija</a></li>
                            <li><a class="dropdown-item" href="{{ route('organization.expiries.index', $organization->slug) }}">Isteci</a></li>
                            <li><a class="dropdown-item" href="{{ route('organization.handovers.index', $organization->slug) }}">Predaje</a></li>
                            <li><a class="dropdown-item" href="{{ $settingsUrl('organizacija', 'ustroj') }}">Struktura</a></li>
                            <li><a class="dropdown-item" href="{{ $settingsUrl('kadar') }}">Postavke</a></li>
                        </ul>
                    </li>
                    @endif

                    @if($canTime && ! $isWorker)
                    @php $vrijemeOpen = request()->routeIs('organization.timesheet.*', 'organization.schedule.*', 'organization.exceptions.*') || (request()->routeIs('organization.settings.*') && request('tab') === 'vrijeme'); @endphp
                    <li class="navbar-modules-group @if($vrijemeOpen) is-open @endif">
                        <div class="navbar-modules-group-head">
                            <a class="dropdown-item navbar-modules-group-link @if($vrijemeOpen) active @endif"
                               href="{{ route('organization.timesheet.index', $organization->slug) }}">Vrijeme</a>
                            <button type="button" class="navbar-modules-group-toggle" aria-expanded="{{ $vrijemeOpen ? 'true' : 'false' }}">
                                <span class="navbar-modules-chevron"></span>
                            </button>
                        </div>
                        <ul class="navbar-modules-submenu list-unstyled mb-0">
                            <li><a class="dropdown-item" href="{{ route('organization.timesheet.index', $organization->slug) }}">Šihterica</a></li>
                            <li><a class="dropdown-item" href="{{ route('organization.schedule.index', $organization->slug) }}">Raspored</a></li>
                            <li><a class="dropdown-item" href="{{ route('organization.exceptions.index', $organization->slug) }}">Iznimke</a></li>
                            <li><a class="dropdown-item" href="{{ route('organization.timesheet.fund', $organization->slug) }}">Mjesečni fond</a></li>
                            @if($rbac->can($organization->id, $userId, 'payroll.export'))
                                <li><a class="dropdown-item" href="{{ route('organization.timesheet.payroll-hours', $organization->slug) }}">Sati za plaće</a></li>
                            @endif
                            @if($rbac->can($organization->id, $userId, 'inspection.export'))
                                <li><a class="dropdown-item" href="{{ route('organization.timesheet.inspection', $organization->slug) }}">Inspekcija</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ $settingsUrl('vrijeme') }}">Postavke</a></li>
                        </ul>
                    </li>
                    @endif

                    @php $mojeOpen = request()->routeIs('organization.clock', 'organization.timesheet.mine', 'organization.requests.*', 'organization.absences.*'); @endphp
                    <li class="navbar-modules-group @if($mojeOpen) is-open @endif">
                        <div class="navbar-modules-group-head">
                            <a class="dropdown-item navbar-modules-group-link @if($mojeOpen) active @endif"
                               href="{{ route('organization.clock', $organization->slug) }}">Moje</a>
                            <button type="button" class="navbar-modules-group-toggle" aria-expanded="{{ $mojeOpen ? 'true' : 'false' }}">
                                <span class="navbar-modules-chevron"></span>
                            </button>
                        </div>
                        <ul class="navbar-modules-submenu list-unstyled mb-0">
                            <li><a class="dropdown-item" href="{{ route('organization.clock', $organization->slug) }}">Prijava / odjava</a></li>
                            <li><a class="dropdown-item" href="{{ route('organization.timesheet.mine', $organization->slug) }}">Moj tjedan</a></li>
                            <li><a class="dropdown-item" href="{{ route('organization.requests.index', $organization->slug) }}">Zahtjevi</a></li>
                            <li><a class="dropdown-item" href="{{ route('organization.absences.calendar', $organization->slug) }}">Kalendar</a></li>
                        </ul>
                    </li>

                    @if($canApprove)
                    @php $odobrenjaOpen = request()->routeIs('organization.approvals.*') || (request()->routeIs('organization.settings.*') && request('tab') === 'odobrenja'); @endphp
                    <li class="navbar-modules-group @if($odobrenjaOpen) is-open @endif">
                        <div class="navbar-modules-group-head">
                            <a class="dropdown-item navbar-modules-group-link @if($odobrenjaOpen) active @endif"
                               href="{{ route('organization.approvals.index', $organization->slug) }}">Odobrenja</a>
                            <button type="button" class="navbar-modules-group-toggle" aria-expanded="{{ $odobrenjaOpen ? 'true' : 'false' }}">
                                <span class="navbar-modules-chevron"></span>
                            </button>
                        </div>
                        <ul class="navbar-modules-submenu list-unstyled mb-0">
                            <li><a class="dropdown-item" href="{{ route('organization.approvals.index', $organization->slug) }}">Inbox</a></li>
                            @if($canSettings)
                                <li><a class="dropdown-item" href="{{ $settingsUrl('odobrenja') }}">Postavke</a></li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    @if($canSettings)
                    @php $postavkeOpen = request()->routeIs('organization.settings.*', 'organization.team.*'); @endphp
                    <li class="navbar-modules-group @if($postavkeOpen) is-open @endif">
                        <div class="navbar-modules-group-head">
                            <a class="dropdown-item navbar-modules-group-link @if($postavkeOpen) active @endif"
                               href="{{ $settingsUrl() }}">Postavke</a>
                            <button type="button" class="navbar-modules-group-toggle" aria-expanded="{{ $postavkeOpen ? 'true' : 'false' }}">
                                <span class="navbar-modules-chevron"></span>
                            </button>
                        </div>
                        <ul class="navbar-modules-submenu list-unstyled mb-0">
                            @if($isOwner || $canPeople)
                                <li><a class="dropdown-item" href="{{ $settingsUrl('organizacija') }}">Organizacija</a></li>
                            @endif
                            @if($canPeople)
                                <li><a class="dropdown-item" href="{{ $settingsUrl('kadar') }}">Kadrovi</a></li>
                            @endif
                            @if($canTime)
                                <li><a class="dropdown-item" href="{{ $settingsUrl('vrijeme') }}">Vrijeme</a></li>
                                <li><a class="dropdown-item" href="{{ $settingsUrl('odobrenja') }}">Odobrenja</a></li>
                            @endif
                            @if($canTeam)
                                <li><a class="dropdown-item" href="{{ $settingsUrl('pristup') }}">Pristup</a></li>
                            @endif
                            @if($canPeople || $canTime)
                                <li><a class="dropdown-item" href="{{ $settingsUrl('podaci') }}">Podaci</a></li>
                            @endif
                            @if($isOwner)
                                <li><a class="dropdown-item" href="{{ $settingsUrl('pretplata') }}">Pretplata</a></li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    @if($workspaceCount > 1)
                    <li>
                        <a class="dropdown-item" href="{{ route('organization.pick') }}">Promijeni organizaciju</a>
                    </li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li class="navbar-modules-user">
                        <div class="navbar-modules-user-card">
                            <div class="navbar-modules-user-avatar">{{ mb_strtoupper(mb_substr($navEmail, 0, 1)) }}</div>
                            <div class="navbar-modules-user-meta min-w-0">
                                <div class="navbar-modules-user-label">Prijavljeni korisnik</div>
                                <div class="navbar-modules-user-email text-truncate">{{ $navEmail }}</div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="mb-0">@csrf
                            <button type="submit" class="btn btn-navbar-logout w-100">Odjava</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
</header>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    document.querySelectorAll('.navbar-modules-group-toggle').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var group = btn.closest('.navbar-modules-group');
            if (!group) return;
            var open = group.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
})();
</script>
@stack('scripts')
</body>
</html>
