<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Odabir tvrtke — HR SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Odaberi tvrtku</h1>
                    <form method="POST" action="{{ route('organization.pick.store') }}">
                        @csrf
                        <div class="list-group mb-3">
                            @foreach($entries as $entry)
                                <label class="list-group-item">
                                    <input type="radio" name="slug" value="{{ $entry->organization->slug }}" class="form-check-input me-2" required>
                                    <strong>{{ $entry->organization->name }}</strong>
                                    <span class="text-muted small">· {{ $entry->role->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('slug')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-primary">Nastavi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
