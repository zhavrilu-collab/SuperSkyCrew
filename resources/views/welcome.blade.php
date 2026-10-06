<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1b431c">
    <title>SuperSkyCrew</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; background: linear-gradient(160deg, #f4f8f4, #fff); font-family: 'Segoe UI', -apple-system, sans-serif; color: #2b3a2b; }
        .hero { max-width: 720px; margin: auto; padding: 2rem; }
        .text-tema { color: #1b431c; }
        .btn-primary { background: #1b431c; border-color: #1b431c; border-radius: 9px; }
        .btn-primary:hover { background: #112b12; border-color: #112b12; }
        .btn-outline-success { color: #1b431c; border-color: #1b431c; border-radius: 9px; }
        .btn-outline-success:hover { background: #1b431c; border-color: #1b431c; color: #fff; }
        .btn-outline-dark { border-radius: 9px; }
        .app-guest-lockup { display: block; max-width: 210px; width: 100%; height: auto; margin: 0 auto 1.15rem; }
    </style>
</head>
<body>
<div class="hero text-center">
    <img src="{{ \App\Support\OrganizationThemes::productLogoUrl() }}" alt="SuperSkyCrew" class="app-guest-lockup">
    <h1 class="display-6 text-tema fw-bold">SuperSkyCrew</h1>
    <p class="lead text-muted">Platforma za upravljanje ljudskim resursima — kadar, evidencija radnog vremena i ustroj tvrtke.</p>
    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
        <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Prijava</a>
        <a href="{{ route('register.organization') }}" class="btn btn-outline-success btn-lg">Registracija tvrtke</a>
        <a href="{{ route('pricing') }}" class="btn btn-outline-dark btn-lg">Cijene</a>
    </div>
    <p class="small text-muted mt-4 mb-0">
        Nakon odobrenja: {{ $trialDays }} dana paketa {{ $trialPlanLabel }}.
        · <a class="text-tema" href="{{ route('pricing') }}">paketi i cijene</a>
    </p>
</div>
</body>
</html>
