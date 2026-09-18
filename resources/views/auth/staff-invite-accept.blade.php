<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prihvat pozivnice — HR SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2">Pozivnica u tim</h1>
                    <p class="text-muted small mb-4">
                        Pozvani ste u tvrtku <strong>{{ $invite->organization->name }}</strong>
                        kao <strong>{{ $invite->role->label() }}</strong>.
                    </p>

                    @if ($errors->any())
                        <div class="alert alert-danger small">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('staff-invite.store', $token) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="name">Ime i prezime</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="email">E-mail</label>
                            <input type="email" id="email" class="form-control" value="{{ $invite->email }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Lozinka</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password_confirmation">Potvrda lozinke</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Prihvati pozivnicu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
