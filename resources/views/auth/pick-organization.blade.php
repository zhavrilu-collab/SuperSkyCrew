@extends('layouts.guest')

@section('title', 'Odabir organizacije — HR SaaS')

@section('content')
<div class="kartica-kontejner">
    <h1 class="h4 text-tema mb-3">Odaberi organizaciju</h1>
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
@endsection
