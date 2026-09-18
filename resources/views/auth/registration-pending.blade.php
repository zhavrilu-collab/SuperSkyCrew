<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Čekanje odobrenja — HR SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Registracija na čekanju</h1>
                    <p class="text-muted">Super-administrator mora odobriti tvrtku prije pristupa sustavu.</p>

                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <ul class="list-group mb-3">
                        @foreach($pendingOrganizations as $organization)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $organization->name }}</span>
                                <span class="badge bg-warning text-dark">{{ $organization->status->label() }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <p class="small text-muted mb-0">Stranica se automatski osvježava svakih 15 sekundi.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
setInterval(() => {
    fetch('{{ route('registration.pending.status') }}')
        .then(r => r.json())
        .then(data => { if (!data.pending && data.redirect_url) window.location = data.redirect_url; });
}, 15000);
</script>
</body>
</html>
