<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registracija tvrtke — HR SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Registracija tvrtke</h1>
                    <p class="text-muted small">Nakon registracije tvrtka čeka odobrenje super-administratora.</p>

                    @if ($errors->any())
                        <div class="alert alert-danger small">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('register.organization') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="name">Naziv tvrtke</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="oib">OIB</label>
                                <input type="text" name="oib" id="oib" class="form-control" value="{{ old('oib') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="organization_email">E-mail tvrtke</label>
                                <input type="email" name="organization_email" id="organization_email" class="form-control" value="{{ old('organization_email') }}" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="phone">Telefon</label>
                                <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="city">Grad</label>
                                <input type="text" name="city" id="city" class="form-control" value="{{ old('city') }}">
                            </div>
                        </div>

                        @unless($isLoggedIn)
                            <hr>
                            <h2 class="h6">Vlasnički račun</h2>
                            <div class="mb-3">
                                <label class="form-label" for="admin_name">Ime i prezime</label>
                                <input type="text" name="admin_name" id="admin_name" class="form-control" value="{{ old('admin_name') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="admin_email">E-mail</label>
                                <input type="email" name="admin_email" id="admin_email" class="form-control" value="{{ old('admin_email') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="password">Lozinka</label>
                                <input type="password" name="password" id="password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="password_confirmation">Potvrda lozinke</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                            </div>
                        @endunless

                        <button type="submit" class="btn btn-primary">Registriraj tvrtku</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
