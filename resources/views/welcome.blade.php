<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f6b64">
    <title>SuperSkyCrew</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; background: linear-gradient(160deg, #eef8f7, #fff); font-family: 'Segoe UI', -apple-system, sans-serif; color: #1a3d3a; }
        .hero { max-width: 720px; margin: auto; padding: 2rem; }
        .text-tema { color: #0f6b64; }
        .btn-primary { background: #0f6b64; border-color: #0f6b64; border-radius: 9px; }
        .btn-primary:hover { background: #08403c; border-color: #08403c; }
        .btn-outline-success { color: #0f6b64; border-color: #0f6b64; border-radius: 9px; }
        .btn-outline-success:hover { background: #0f6b64; border-color: #0f6b64; color: #fff; }
        .btn-outline-dark { border-radius: 9px; }
        .app-guest-lockup { display: block; max-width: 210px; width: 100%; height: auto; margin: 0 auto 1.15rem; }
    </style>
</head>
<body>
<div class="hero text-center">
    <img src="{{ asset('brand/superskycrew-lockup.png') }}" alt="SuperSkyCrew" class="app-guest-lockup">
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
