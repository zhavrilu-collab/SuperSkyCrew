<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $organization->themePalette()['primary'] }}">
    <title>{{ $organization->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.platform-styles')
    @include('partials.organization-theme', ['organization' => $organization])
    <style>
        html, body { height: 100%; }
        body {
            background: linear-gradient(160deg, var(--svijetlo-zelena) 0%, #e8f0e8 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }
        .top-bar {
            background: white;
            border-bottom: 1px solid #e0e8e0;
            padding: 10px 0;
            min-height: 52px;
            font-size: 13px;
            flex-shrink: 0;
        }
        .top-bar .landing-user {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 0;
        }
        .top-bar .landing-user-avatar {
            flex-shrink: 0;
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primarna-zelena), var(--primarna-tamna));
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            border: 2px solid rgba(212, 175, 55, 0.55);
        }
        .top-bar .landing-user-meta { min-width: 0; }
        .top-bar .landing-user-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #7a8a7a;
            line-height: 1.2;
        }
        .top-bar .landing-user-email {
            font-size: 13px;
            font-weight: 600;
            color: var(--tekst-tamni);
            line-height: 1.2;
        }
        .btn-landing-logout {
            border-radius: 8px !important;
            border: 1px solid rgba(27, 67, 28, 0.22) !important;
            background: #fff !important;
            color: var(--primarna-zelena) !important;
            font-weight: 600 !important;
            font-size: 12px !important;
            padding: .4rem .9rem !important;
            min-height: 2.15rem;
        }
        .btn-landing-logout:hover {
            background: var(--primarna-zelena) !important;
            border-color: var(--primarna-zelena) !important;
            color: #fff !important;
        }
        .hero {
            background: linear-gradient(135deg, var(--primarna-zelena) 0%, var(--primarna-tamna) 100%);
            border-bottom: 3px solid var(--zlatna-tradicija);
            padding: 22px 14px 24px;
            text-align: center;
            color: white;
            flex-shrink: 0;
        }
        .hero img { max-height: 64px; width: auto; margin-bottom: 10px; background: white; border-radius: 6px; padding: 4px; }
        .hero h1 { font-size: 1.4rem; font-weight: 700; margin: 0; color: #fff; }
        .hero p { color: rgba(255,255,255,0.75); font-size: 0.88rem; margin-top: 5px; margin-bottom: 0; }
        .moduli {
            padding: 20px 14px;
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            max-width: none;
            width: 100%;
        }
        .moduli > .container { width: 100%; max-width: 960px; }
        .footer-landing { text-align: center; padding: 10px; color: #aaa; font-size: 11px; border-top: 1px solid #e0e8e0; flex-shrink: 0; }
        @media (max-width: 991px) {
            .moduli { align-items: flex-start; }
        }
        @media (max-width: 576px) {
            .hero { padding: 16px 12px 18px; }
            .hero h1 { font-size: 1.2rem; }
            .moduli { padding: 14px 10px; }
            .kartica-modula { padding: 14px 10px; }
        }
    </style>
</head>
<body>
@php
    $workspaceCount = \App\Support\UserOrganizationNavigation::organizationUsers((int) auth()->id())
        ->filter(fn ($entry) => $entry->organization?->status === \App\Enums\OrganizationStatus::Active)
        ->count();
@endphp
<div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div class="landing-user">
            <div class="landing-user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->email, 0, 1)) }}</div>
            <div class="landing-user-meta">
                <div class="landing-user-label">Prijavljeni korisnik</div>
                <div class="landing-user-email text-truncate">
                    {{ auth()->user()->email }}
                    @if($membership?->role)
                        · {{ $membership->role->label() }}
                    @endif
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($workspaceCount > 1)
                <a href="{{ route('organization.pick') }}" class="btn btn-sm btn-outline-secondary">Promijeni organizaciju</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="mb-0">@csrf
                <button class="btn btn-landing-logout" type="submit">Odjava</button>
            </form>
        </div>
    </div>
</div>

@if(session('status'))
    <div class="container pt-2" style="flex-shrink:0">
        <div class="alert alert-success mb-0">{{ session('status') }}</div>
    </div>
@endif
@if($errors->any())
    <div class="container pt-2" style="flex-shrink:0">
        <div class="alert alert-danger mb-0">{{ $errors->first() }}</div>
    </div>
@endif

<div class="hero">
    @if($organization->logoUrl())
        <img src="{{ $organization->logoUrl() }}" alt="">
    @endif
    <h1>{{ $organization->name }}</h1>
    <p>Upravljanje ljudskim resursima — odaberite modul</p>
</div>

<div class="moduli">
    <div class="container">
        <div class="row g-3">
            <div class="col-12 col-sm-6 col-md-4">
                <a href="{{ route('organization.dashboard', $organization->slug) }}" class="kartica-modula">
                    <span class="ikona" aria-hidden="true">📊</span>
                    <h3>Pregled</h3>
                    <p>Nadzorna ploča, prisutnost, isteci i iznimke.</p>
                    <span class="badge-modul">Aktivno</span>
                </a>
            </div>
            @if($canPeople && ! $isWorker)
            <div class="col-12 col-sm-6 col-md-4">
                <a href="{{ route('organization.people.index', $organization->slug) }}" class="kartica-modula">
                    <span class="ikona" aria-hidden="true">👥</span>
                    <h3>Kadrovi</h3>
                    <p>Evidencija osoba prema Pravilniku NN 55/2024.</p>
                    <span class="badge-modul">Aktivno</span>
                </a>
            </div>
            @endif
            @if($canTime && ! $isWorker)
            <div class="col-12 col-sm-6 col-md-4">
                <a href="{{ route('organization.timesheet.index', $organization->slug) }}" class="kartica-modula">
                    <span class="ikona" aria-hidden="true">⏱</span>
                    <h3>Vrijeme</h3>
                    <p>Šihterica, raspored, iznimke i inspekcijski izvoz.</p>
                    <span class="badge-modul">Aktivno</span>
                </a>
            </div>
            @endif
            <div class="col-12 col-sm-6 col-md-4">
                <a href="{{ route('organization.clock', $organization->slug) }}" class="kartica-modula">
                    <span class="ikona" aria-hidden="true">🙋</span>
                    <h3>Moje</h3>
                    <p>Prijava i odjava, moj tjedan, zahtjevi i kalendar.</p>
                    <span class="badge-modul">Aktivno</span>
                </a>
            </div>
            @if($isWorker)
            <div class="col-12 col-sm-6 col-md-4">
                <a href="{{ route('organization.timesheet.mine', $organization->slug) }}" class="kartica-modula">
                    <span class="ikona" aria-hidden="true">📅</span>
                    <h3>Moj tjedan</h3>
                    <p>Uvid u vlastitu evidenciju radnog vremena.</p>
                    <span class="badge-modul">Aktivno</span>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <a href="{{ route('organization.requests.index', $organization->slug) }}" class="kartica-modula">
                    <span class="ikona" aria-hidden="true">📝</span>
                    <h3>Zahtjevi</h3>
                    <p>Godišnji odmor, odsutnosti i ispravci prijave.</p>
                    <span class="badge-modul">Aktivno</span>
                </a>
            </div>
            @endif
            @if($canApprove)
            <div class="col-12 col-sm-6 col-md-4">
                <a href="{{ route('organization.approvals.index', $organization->slug) }}" class="kartica-modula">
                    <span class="ikona" aria-hidden="true">✅</span>
                    <h3>Odobrenja</h3>
                    <p>Inbox zahtjeva za odobrenje ili odbijanje.</p>
                    <span class="badge-modul">Aktivno</span>
                </a>
            </div>
            @endif
            @if($canSettings)
            <div class="col-12 col-sm-6 col-md-4">
                <a href="{{ route('organization.settings.index', $organization->slug) }}" class="kartica-modula">
                    <span class="ikona" aria-hidden="true">⚙</span>
                    <h3>Postavke</h3>
                    <p>Izgled, ustroj i konfiguracija po modulima.</p>
                    <span class="badge-modul">Aktivno</span>
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="footer-landing">{{ $organization->name }} &copy; {{ date('Y') }} — Upravljanje ljudskim resursima</div>
</body>
</html>
