<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prijava — HR SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">HR SaaS</h1>
                    <p class="text-muted small">Prijava u platformu za upravljanje ljudskim resursima.</p>

                    @if ($errors->any())
                        <div class="alert alert-danger small">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="email">E-mail</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Lozinka</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Prijava</button>
                    </form>

                    @if($googleLoginUrl || $microsoftLoginUrl)
                        <hr class="my-4">
                        <div class="d-grid gap-2">
                            @if($googleLoginUrl)
                                <a href="{{ $googleLoginUrl }}" class="btn btn-outline-secondary btn-sm">Prijava s Googleom</a>
                            @endif
                            @if($microsoftLoginUrl)
                                <a href="{{ $microsoftLoginUrl }}" class="btn btn-outline-secondary btn-sm">Prijava s Microsoftom</a>
                            @endif
                        </div>
                    @endif

                    @if($coreAuthEnabled)
                        <p class="text-muted small mt-3 mb-0">Autentifikacija ide preko Core platforme.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
