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
        $ustrojTvrtkeOpen = $isUstrojSection && \App\Support\StructureCatalog::isUstrojTvrtke($ustrojKatalog);
        $sistemaOpen = request()->routeIs('organization.systematization.*');
        $osnovniUrl = $settingsUrl('organizacija', 'osnovni-podaci');
        $profilHome = $isOwner ? $osnovniUrl : $ustrojUrl('pravne');
    @endphp
    <a class="app-sidebar-link @if($profilOpen) active @endif"
       href="{{ $profilHome }}">@include('partials.nav-icon', ['name' => 'building'])Profil tvrtke</a>

    <a class="app-sidebar-link @if($ustrojTvrtkeOpen) active @endif"
       href="{{ $ustrojUrl('poslovna') }}">@include('partials.nav-icon', ['name' => 'diagram'])Ustroj tvrtke</a>

    <a class="app-sidebar-link @if($sistemaOpen) active @endif"
       href="{{ route('organization.systematization.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'book'])Sistematizacija</a>

    @php $kadarOpen = request()->routeIs('organization.people.*', 'organization.expiries.*', 'organization.handovers.*', 'organization.contracts.index', 'organization.document-creator.*', 'organization.family.*'); @endphp
    <div class="app-sidebar-group @if($kadarOpen) is-open @endif">
        <div class="app-sidebar-group-head">
            <a class="app-sidebar-group-link @if($kadarOpen) active @endif"
               href="{{ route('organization.people.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'people'])Djelatnici</a>
            <button type="button" class="app-sidebar-group-toggle" aria-expanded="{{ $kadarOpen ? 'true' : 'false' }}">
                <span class="app-sidebar-chevron"></span>
            </button>
        </div>
        <div class="app-sidebar-submenu">
            <a class="app-sidebar-link @if(request()->routeIs('organization.people.*')) active @endif" href="{{ route('organization.people.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'id-card'])Registar djelatnika</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.contracts.index')) active @endif" href="{{ route('organization.contracts.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'contract'])Ugovori o radu</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.document-creator.*')) active @endif" href="{{ route('organization.document-creator.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'pen'])Izrada dokumenata</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.family.*')) active @endif" href="{{ route('organization.family.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'family'])Članovi obitelji</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.expiries.*')) active @endif" href="{{ route('organization.expiries.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'hourglass'])Isteci</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.handovers.*')) active @endif" href="{{ route('organization.handovers.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'swap'])Predaje</a>
        </div>
    </div>
    @endif

    @if($canTime && ! $isWorker)
    @php $vrijemeOpen = request()->routeIs('organization.timesheet.*', 'organization.schedule.*', 'organization.exceptions.*', 'organization.grants.*'); @endphp
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
        </div>
    </div>
    @endif

    @php $mojeOpen = request()->routeIs('organization.clock', 'organization.timesheet.mine', 'organization.requests.*', 'organization.absences.*', 'organization.my-documents.*'); @endphp
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
            <a class="app-sidebar-link @if(request()->routeIs('organization.my-documents.*')) active @endif" href="{{ route('organization.my-documents.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'file'])Dokumenti</a>
            @if($organization->feature(\App\Support\OrganizationFeatures::SHIFT_BOARD))
                <a class="app-sidebar-link @if(request()->routeIs('organization.schedule.*')) active @endif" href="{{ route('organization.schedule.index', $organization->slug) }}">@include('partials.nav-icon', ['name' => 'shifts'])Raspored / smjene</a>
            @endif
        </div>
    </div>

    @if($canApprove)
    @php $odobrenjaOpen = request()->routeIs('organization.approvals.*'); @endphp
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
        </div>
    </div>
    @endif

    @if($canSettings)
    @php
        $postavkeOpen = request()->routeIs('organization.settings.*', 'organization.team.*')
            && request('section') !== 'ustroj'
            && request('section') !== 'osnovni-podaci';
        $settingsNav = \App\Support\SettingsCatalog::sidebarGroups($isOwner, $canPeople, $canPeople || $canTime, $canTeam);
        $settingsHome = $settingsNav[0]['items'][0] ?? null;
        $settingsItemActive = function (string $tab, ?string $section) use ($isOwner): bool {
            if (! request()->routeIs('organization.settings.*', 'organization.team.*')) {
                return false;
            }
            if (request()->routeIs('organization.team.*')) {
                return $tab === \App\Support\SettingsCatalog::TAB_PRISTUP && $section === 'korisnici';
            }
            $currentTab = (string) request('tab', $isOwner ? 'organizacija' : 'kadar');
            if ($currentTab !== $tab) {
                return false;
            }
            $currentSection = (string) request('section', '');
            if ($currentSection === '') {
                $currentSection = \App\Support\SettingsCatalog::defaultSection($tab, $isOwner);
            }

            return ($section ?? '') === $currentSection;
        };
    @endphp
    <div class="app-sidebar-group @if($postavkeOpen) is-open @endif">
        <div class="app-sidebar-group-head">
            <a class="app-sidebar-group-link @if($postavkeOpen) active @endif"
               href="{{ $settingsHome ? $settingsUrl($settingsHome['tab'], $settingsHome['section']) : $settingsUrl() }}">@include('partials.nav-icon', ['name' => 'gear'])Postavke</a>
            <button type="button" class="app-sidebar-group-toggle" aria-expanded="{{ $postavkeOpen ? 'true' : 'false' }}">
                <span class="app-sidebar-chevron"></span>
            </button>
        </div>
        <div class="app-sidebar-submenu">
            @foreach($settingsNav as $navGroup)
                @if($navGroup['label'])
                    <div class="app-sidebar-label">{{ $navGroup['label'] }}</div>
                @endif
                @foreach($navGroup['items'] as $item)
                    <a class="app-sidebar-link @if($settingsItemActive($item['tab'], $item['section'])) active @endif @if(! $item['icon']) app-sidebar-link-plain @endif"
                       href="{{ $settingsUrl($item['tab'], $item['section']) }}">
                        @if($item['icon'])
                            @include('partials.nav-icon', ['name' => $item['icon']])
                        @endif
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endforeach
        </div>
    </div>
    @endif

    @if($workspaceCount > 1)
        <a class="app-sidebar-link" href="{{ route('organization.pick') }}">@include('partials.nav-icon', ['name' => 'switch'])Promijeni organizaciju</a>
    @endif
</nav>
