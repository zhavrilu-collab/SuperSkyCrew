@include('partials.nav-icons')
<nav class="app-sidebar-nav" aria-label="Moduli">
    <a class="app-sidebar-link @if(request()->routeIs('organization.dashboard')) active @endif"
       href="{{ route('organization.dashboard', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'home'])Nadzorna ploča</a>

    @if($canPeople && ! $isWorker)
    @php
        $ustrojKatalog = \App\Support\StructureCatalog::resolve(request('katalog'));
        $isUstrojSection = (request()->routeIs('organization.settings.*') && request('section') === 'ustroj')
            || request()->routeIs('organization.structure.*');
        $isOsnovni = request()->routeIs('organization.settings.*') && request('section') === 'osnovni-podaci';
        $profilOpen = $isOsnovni || ($isUstrojSection && \App\Support\StructureCatalog::isProfil($ustrojKatalog));
        $ustrojTvrtkeOpen = request()->routeIs('organization.segments.*', 'organization.positions.index')
            || ($isUstrojSection && \App\Support\StructureCatalog::isUstrojTvrtke($ustrojKatalog));
        $sistemaOpen = request()->routeIs('organization.systematization.*');
        $osnovniUrl = $settingsUrl('organizacija', 'osnovni-podaci');
        $profilHome = $isOwner ? $osnovniUrl : $ustrojUrl('pravne');
    @endphp
    <div class="app-sidebar-group @if($profilOpen) is-open @endif">
        <div class="app-sidebar-group-head">
            <a class="app-sidebar-group-link @if($profilOpen) active @endif"
               href="{{ $profilHome }}">@include('partials.nav-icon', ['name' => 'building'])Profil tvrtke</a>
            <button type="button" class="app-sidebar-group-toggle" aria-expanded="{{ $profilOpen ? 'true' : 'false' }}">
                <span class="app-sidebar-chevron"></span>
            </button>
        </div>
        <div class="app-sidebar-submenu">
            @if($isOwner)
                <a class="app-sidebar-link @if($isOsnovni) active @endif" href="{{ $osnovniUrl }}">@include('partials.nav-icon', ['name' => 'clipboard'])Osnovni podaci</a>
            @endif
            <a class="app-sidebar-link @if($isUstrojSection && $ustrojKatalog === 'pravne') active @endif" href="{{ $ustrojUrl('pravne') }}">@include('partials.nav-icon', ['name' => 'buildings'])Članice grupacije</a>
            <a class="app-sidebar-link @if($isUstrojSection && $ustrojKatalog === 'poslovnice') active @endif" href="{{ $ustrojUrl('poslovnice') }}">@include('partials.nav-icon', ['name' => 'shop'])Poslovnice</a>
            <a class="app-sidebar-link @if($isUstrojSection && $ustrojKatalog === 'troskovi') active @endif" href="{{ $ustrojUrl('troskovi') }}">@include('partials.nav-icon', ['name' => 'coins'])Mjesta troška</a>
        </div>
    </div>

    <div class="app-sidebar-group @if($ustrojTvrtkeOpen) is-open @endif">
        <div class="app-sidebar-group-head">
            <a class="app-sidebar-group-link @if($ustrojTvrtkeOpen) active @endif"
               href="{{ $ustrojUrl('poslovna') }}">@include('partials.nav-icon', ['name' => 'diagram'])Ustroj tvrtke</a>
            <button type="button" class="app-sidebar-group-toggle" aria-expanded="{{ $ustrojTvrtkeOpen ? 'true' : 'false' }}">
                <span class="app-sidebar-chevron"></span>
            </button>
        </div>
        <div class="app-sidebar-submenu">
            <a class="app-sidebar-link @if(request()->routeIs('organization.segments.*')) active @endif" href="{{ route('organization.segments.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'layers'])Poslovni segmenti</a>
            <a class="app-sidebar-link @if($isUstrojSection && $ustrojKatalog === 'poslovna') active @endif" href="{{ $ustrojUrl('poslovna') }}">@include('partials.nav-icon', ['name' => 'tree'])Poslovni ustroj</a>
            <a class="app-sidebar-link @if($isUstrojSection && $ustrojKatalog === 'funkcijska') active @endif" href="{{ $ustrojUrl('funkcijska') }}">@include('partials.nav-icon', ['name' => 'sitemap'])Funkcionalni ustroj</a>
            <a class="app-sidebar-link @if($isUstrojSection && $ustrojKatalog === 'mjesta') active @endif" href="{{ $ustrojUrl('mjesta') }}">@include('partials.nav-icon', ['name' => 'briefcase'])Radna mjesta</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.positions.index')) active @endif" href="{{ route('organization.positions.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'chairs'])Radne pozicije</a>
            <a class="app-sidebar-link @if($isUstrojSection && $ustrojKatalog === 'organigram') active @endif" href="{{ $ustrojUrl('organigram') }}">@include('partials.nav-icon', ['name' => 'share'])Organizacijska shema</a>
        </div>
    </div>

    <div class="app-sidebar-group @if($sistemaOpen) is-open @endif">
        <div class="app-sidebar-group-head">
            <a class="app-sidebar-group-link @if($sistemaOpen) active @endif"
               href="{{ route('organization.systematization.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'book'])Sistematizacija</a>
            <button type="button" class="app-sidebar-group-toggle" aria-expanded="{{ $sistemaOpen ? 'true' : 'false' }}">
                <span class="app-sidebar-chevron"></span>
            </button>
        </div>
        <div class="app-sidebar-submenu">
            <a class="app-sidebar-link @if(request()->routeIs('organization.systematization.index')) active @endif" href="{{ route('organization.systematization.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'file-text'])Opisi radnih mjesta</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.systematization.plan')) active @endif" href="{{ route('organization.systematization.plan', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'layout'])Plan radnih pozicija</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.systematization.competencies')) active @endif" href="{{ route('organization.systematization.competencies', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'certificate'])Kompetencije</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.systematization.acts')) active @endif" href="{{ route('organization.systematization.acts', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'stamp'])Interni akti</a>
        </div>
    </div>

    @php $kadarOpen = request()->routeIs('organization.people.*', 'organization.expiries.*', 'organization.handovers.*', 'organization.contracts.index', 'organization.document-creator.*', 'organization.family.*') || (request()->routeIs('organization.settings.*') && request('tab') === 'kadar'); @endphp
    <div class="app-sidebar-group @if($kadarOpen) is-open @endif">
        <div class="app-sidebar-group-head">
            <a class="app-sidebar-group-link @if($kadarOpen) active @endif"
               href="{{ route('organization.people.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'people'])Zaposlenici</a>
            <button type="button" class="app-sidebar-group-toggle" aria-expanded="{{ $kadarOpen ? 'true' : 'false' }}">
                <span class="app-sidebar-chevron"></span>
            </button>
        </div>
        <div class="app-sidebar-submenu">
            <a class="app-sidebar-link @if(request()->routeIs('organization.people.*')) active @endif" href="{{ route('organization.people.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'id-card'])Dosjei zaposlenika</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.contracts.index')) active @endif" href="{{ route('organization.contracts.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'contract'])Ugovori o radu</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.document-creator.*')) active @endif" href="{{ route('organization.document-creator.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'pen'])Izrada dokumenata</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.family.*')) active @endif" href="{{ route('organization.family.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'family'])Članovi obitelji</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.expiries.*')) active @endif" href="{{ route('organization.expiries.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'hourglass'])Isteci</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.handovers.*')) active @endif" href="{{ route('organization.handovers.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'swap'])Predaje</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.settings.*') && request('tab') === 'kadar') active @endif" href="{{ $settingsUrl('kadar') }}">@include('partials.nav-icon', ['name' => 'sliders'])Postavke</a>
        </div>
    </div>
    @endif

    @if($canTime && ! $isWorker)
    @php $vrijemeOpen = request()->routeIs('organization.timesheet.*', 'organization.schedule.*', 'organization.exceptions.*', 'organization.grants.*') || (request()->routeIs('organization.settings.*') && request('tab') === 'vrijeme'); @endphp
    <div class="app-sidebar-group @if($vrijemeOpen) is-open @endif">
        <div class="app-sidebar-group-head">
            <a class="app-sidebar-group-link @if($vrijemeOpen) active @endif"
               href="{{ route('organization.timesheet.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'clock'])Vrijeme</a>
            <button type="button" class="app-sidebar-group-toggle" aria-expanded="{{ $vrijemeOpen ? 'true' : 'false' }}">
                <span class="app-sidebar-chevron"></span>
            </button>
        </div>
        <div class="app-sidebar-submenu">
            <a class="app-sidebar-link @if(request()->routeIs('organization.timesheet.index', 'organization.timesheet.show')) active @endif" href="{{ route('organization.timesheet.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'timesheet'])Šihterica</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.schedule.*')) active @endif" href="{{ route('organization.schedule.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'calendar-range'])Raspored</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.exceptions.*')) active @endif" href="{{ route('organization.exceptions.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'alert'])Iznimke</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.timesheet.fund')) active @endif" href="{{ route('organization.timesheet.fund', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'wallet'])Mjesečni fond</a>
            @if($rbac->can($organization->id, $userId, 'payroll.export'))
                <a class="app-sidebar-link @if(request()->routeIs('organization.timesheet.payroll-hours')) active @endif" href="{{ route('organization.timesheet.payroll-hours', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'banknote'])Sati za plaće</a>
            @endif
            @if($rbac->can($organization->id, $userId, 'inspection.export') && $organization->feature(\App\Support\OrganizationFeatures::INSPECTION_EXPORT))
                <a class="app-sidebar-link @if(request()->routeIs('organization.timesheet.inspection')) active @endif" href="{{ route('organization.timesheet.inspection', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'search'])Inspekcija</a>
            @endif
            @if($organization->feature(\App\Support\OrganizationFeatures::GRANT_HOURS))
                <a class="app-sidebar-link @if(request()->routeIs('organization.grants.*')) active @endif" href="{{ route('organization.grants.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'gift'])Grant sati</a>
            @endif
            <a class="app-sidebar-link @if(request()->routeIs('organization.settings.*') && request('tab') === 'vrijeme') active @endif" href="{{ $settingsUrl('vrijeme') }}">@include('partials.nav-icon', ['name' => 'list'])Postavke</a>
        </div>
    </div>
    @endif

    @php $mojeOpen = request()->routeIs('organization.clock', 'organization.timesheet.mine', 'organization.requests.*', 'organization.absences.*'); @endphp
    <div class="app-sidebar-group @if($mojeOpen) is-open @endif">
        <div class="app-sidebar-group-head">
            <a class="app-sidebar-group-link @if($mojeOpen) active @endif"
               href="{{ route('organization.clock', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'user'])Moje</a>
            <button type="button" class="app-sidebar-group-toggle" aria-expanded="{{ $mojeOpen ? 'true' : 'false' }}">
                <span class="app-sidebar-chevron"></span>
            </button>
        </div>
        <div class="app-sidebar-submenu">
            <a class="app-sidebar-link @if(request()->routeIs('organization.clock')) active @endif" href="{{ route('organization.clock', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'punch'])Prijava / odjava</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.timesheet.mine')) active @endif" href="{{ route('organization.timesheet.mine', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'week'])Moj tjedan</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.requests.*')) active @endif" href="{{ route('organization.requests.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'mail'])Zahtjevi</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.absences.*')) active @endif" href="{{ route('organization.absences.calendar', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'calendar'])Kalendar</a>
            @if($organization->feature(\App\Support\OrganizationFeatures::SHIFT_BOARD))
                <a class="app-sidebar-link @if(request()->routeIs('organization.schedule.*')) active @endif" href="{{ route('organization.schedule.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'shifts'])Raspored / smjene</a>
            @endif
        </div>
    </div>

    @if($canApprove)
    @php $odobrenjaOpen = request()->routeIs('organization.approvals.*') || (request()->routeIs('organization.settings.*') && request('tab') === 'odobrenja'); @endphp
    <div class="app-sidebar-group @if($odobrenjaOpen) is-open @endif">
        <div class="app-sidebar-group-head">
            <a class="app-sidebar-group-link @if($odobrenjaOpen) active @endif"
               href="{{ route('organization.approvals.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'check'])Odobrenja</a>
            <button type="button" class="app-sidebar-group-toggle" aria-expanded="{{ $odobrenjaOpen ? 'true' : 'false' }}">
                <span class="app-sidebar-chevron"></span>
            </button>
        </div>
        <div class="app-sidebar-submenu">
            <a class="app-sidebar-link @if(request()->routeIs('organization.approvals.*')) active @endif" href="{{ route('organization.approvals.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'inbox'])Inbox</a>
            @if($canSettings)
                <a class="app-sidebar-link @if(request()->routeIs('organization.settings.*') && request('tab') === 'odobrenja') active @endif" href="{{ $settingsUrl('odobrenja') }}">@include('partials.nav-icon', ['name' => 'flow'])Postavke</a>
            @endif
        </div>
    </div>
    @endif

    @if($canSettings)
    @php
        $postavkeOpen = request()->routeIs('organization.settings.*', 'organization.team.*')
            && request('section') !== 'ustroj'
            && request('section') !== 'osnovni-podaci';
    @endphp
    <div class="app-sidebar-group @if($postavkeOpen) is-open @endif">
        <div class="app-sidebar-group-head">
            <a class="app-sidebar-group-link @if($postavkeOpen) active @endif"
               href="{{ $settingsUrl() }}">@include('partials.nav-icon', ['name' => 'gear'])Postavke</a>
            <button type="button" class="app-sidebar-group-toggle" aria-expanded="{{ $postavkeOpen ? 'true' : 'false' }}">
                <span class="app-sidebar-chevron"></span>
            </button>
        </div>
        <div class="app-sidebar-submenu">
            @if($isOwner)
                <a class="app-sidebar-link @if(request()->routeIs('organization.settings.*') && request('tab', 'organizacija') === 'organizacija' && request('section') !== 'ustroj' && request('section') !== 'osnovni-podaci') active @endif" href="{{ $settingsUrl('organizacija') }}">@include('partials.nav-icon', ['name' => 'palette'])Organizacija</a>
            @endif
            @if($canPeople)
                <a class="app-sidebar-link @if(request()->routeIs('organization.settings.*') && request('tab') === 'kadar') active @endif" href="{{ $settingsUrl('kadar') }}">@include('partials.nav-icon', ['name' => 'folder'])Kadrovi</a>
            @endif
            @if($canTime)
                <a class="app-sidebar-link @if(request()->routeIs('organization.settings.*') && request('tab') === 'vrijeme') active @endif" href="{{ $settingsUrl('vrijeme') }}">@include('partials.nav-icon', ['name' => 'list'])Vrijeme</a>
                <a class="app-sidebar-link @if(request()->routeIs('organization.settings.*') && request('tab') === 'odobrenja') active @endif" href="{{ $settingsUrl('odobrenja') }}">@include('partials.nav-icon', ['name' => 'flow'])Odobrenja</a>
            @endif
            @if($canTeam)
                <a class="app-sidebar-link @if(request()->routeIs('organization.settings.*') && request('tab') === 'pristup') active @endif" href="{{ $settingsUrl('pristup') }}">@include('partials.nav-icon', ['name' => 'key'])Pristup</a>
            @endif
            @if($canPeople || $canTime)
                <a class="app-sidebar-link @if(request()->routeIs('organization.settings.*') && request('tab') === 'podaci') active @endif" href="{{ $settingsUrl('podaci') }}">@include('partials.nav-icon', ['name' => 'database'])Podaci</a>
            @endif
            @if($isOwner)
                <a class="app-sidebar-link @if(request()->routeIs('organization.settings.*') && request('tab') === 'pretplata') active @endif" href="{{ $settingsUrl('pretplata') }}">@include('partials.nav-icon', ['name' => 'card'])Pretplata</a>
            @endif
        </div>
    </div>
    @endif

    @if($workspaceCount > 1)
        <a class="app-sidebar-link" href="{{ route('organization.pick') }}">@include('partials.nav-icon', ['name' => 'switch'])Promijeni organizaciju</a>
    @endif
</nav>
<div class="app-sidebar-footer">{{ now()->year }} © SuperSkyCrew</div>
