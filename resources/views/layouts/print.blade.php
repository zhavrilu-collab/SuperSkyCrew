<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $organization->themePalette()['primary'] }}">
    <title>@yield('title', $organization->name)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.platform-styles')
    @include('partials.organization-theme', ['organization' => $organization])
    <style>
        .ispis-dokument { max-width: 820px; margin: 0 auto; }
        .ispis-dokument--wide { max-width: 1100px; }
        .ispis-zaglavlje {
            background: linear-gradient(135deg, var(--primarna-zelena) 0%, var(--primarna-tamna) 100%);
            color: #fff;
            border-radius: 12px 12px 0 0;
            margin: -24px -24px 22px;
            padding: 16px 22px 14px;
            border-bottom: 3px solid var(--zlatna-tradicija);
        }
        .ispis-zaglavlje img { max-height: 40px; background: #fff; border-radius: 6px; padding: 2px; }
        .ispis-zaglavlje .org { font-size: 1.05rem; font-weight: 700; margin: 0; }
        .ispis-zaglavlje .meta { color: rgba(255,255,255,.75); font-size: 12px; margin: 2px 0 0; }
        .ispis-dokument h1 { color: var(--primarna-zelena); }
        .ispis-dokument .list-group-item { border-color: var(--tema-rub-tablica); }
        .sign { min-height: 80px; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            a { text-decoration: none; color: inherit; }
            .kartica-kontejner { box-shadow: none; border: 1px solid #ddd; }
            .ispis-zaglavlje { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
    @stack('styles')
</head>
<body>
@php $documentClass = trim($__env->yieldContent('document-class')); @endphp
<main class="container py-4">
    @hasSection('toolbar')
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2 no-print ispis-dokument {{ $documentClass }}">
            @yield('toolbar')
        </div>
    @endif
    <article class="kartica-kontejner ispis-dokument {{ $documentClass }}">
        <header class="ispis-zaglavlje d-flex align-items-center gap-3">
            @if($organization->logoUrl())
                <img src="{{ $organization->logoUrl() }}" alt="">
            @endif
            <div>
                <p class="org">{{ $organization->name }}</p>
                <p class="meta mb-0">
                    @if($organization->oib) OIB {{ $organization->oib }} · @endif
                    Upravljanje ljudskim resursima
                </p>
            </div>
        </header>
        @yield('content')
    </article>
</main>
</body>
</html>
