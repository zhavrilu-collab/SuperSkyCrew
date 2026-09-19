@extends('layouts.organization')

@section('title', 'Ulaz — '.$location->name)
@section('nav-suffix', 'Moje')

@section('content')
<div class="page-heading">
    <h1>Ulaz · {{ $location->name }}</h1>
    <p class="text-muted mb-0">{{ $person->fullName() }} · {{ $clockedIn ? 'prijavljeni' : 'niste prijavljeni' }}</p>
</div>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="kartica-kontejner">
    <p>Skenirani QR lokacije. Jedan gumb upisuje prijavu ili odjavu na ovom ulazu.</p>
    <form method="POST" action="{{ route('organization.entrance.store', [$organization->slug, $location->entrance_token]) }}">
        @csrf
        <input type="hidden" name="type" value="{{ $nextType->value }}">
        <input type="hidden" name="client_event_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <button class="btn btn-primary btn-lg" type="submit">{{ $nextType->label() }}</button>
        <a class="btn btn-outline-secondary" href="{{ route('organization.clock', $organization->slug) }}">PWA prijava</a>
    </form>
</div>
@endsection
