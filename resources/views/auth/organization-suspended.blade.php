<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pristup suspendiran — HR SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <h1 class="h4 mb-3">Pristup suspendiran</h1>
                    <p class="text-muted mb-4">
                        Pristup tvrtki <strong>{{ $organization->name }}</strong> je privremeno onemogućen.
                        Kontaktirajte podršku ili super-administratora za više informacija.
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('organization.pick') }}" class="btn btn-outline-secondary">Odaberi drugu tvrtku</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Odjava</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
