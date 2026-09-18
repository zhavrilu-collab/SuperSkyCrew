<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'HR SaaS')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
@php($organization = app('currentOrganization'))
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand">{{ $organization->name }}</span>
        <div class="d-flex gap-2">
            <a href="{{ route('organization.clock', $organization->slug) }}" class="btn btn-success btn-sm">Prijava / odjava</a>
            <a href="{{ route('organization.timesheet.mine', $organization->slug) }}" class="btn btn-outline-light btn-sm">Moj tjedan</a>
            <a href="{{ route('organization.requests.index', $organization->slug) }}" class="btn btn-outline-light btn-sm">Zahtjevi</a>
            <a href="{{ route('organization.absences.calendar', $organization->slug) }}" class="btn btn-outline-light btn-sm">Kalendar</a>
            @if(auth()->user() && app(\App\Services\OrganizationRbacService::class)->can($organization->id, auth()->id(), 'requests.approve'))
                <a href="{{ route('organization.approvals.index', $organization->slug) }}" class="btn btn-outline-light btn-sm">Odobrenja</a>
            @endif
            @if(auth()->user() && app(\App\Services\OrganizationRbacService::class)->can($organization->id, auth()->id(), 'people.access'))
                <a href="{{ route('organization.people.index', $organization->slug) }}" class="btn btn-outline-light btn-sm">Kadar</a>
                <a href="{{ route('organization.structure.index', $organization->slug) }}" class="btn btn-outline-light btn-sm">Struktura</a>
                <a href="{{ route('organization.expiries.index', $organization->slug) }}" class="btn btn-outline-light btn-sm">Isteci</a>
                <a href="{{ route('organization.handovers.index', $organization->slug) }}" class="btn btn-outline-light btn-sm">Predaje</a>
            @endif
            @if(auth()->user() && (app(\App\Services\OrganizationRbacService::class)->can($organization->id, auth()->id(), 'time.access') || app(\App\Services\OrganizationRbacService::class)->can($organization->id, auth()->id(), 'payroll.export')))
                <a href="{{ route('organization.timesheet.index', $organization->slug) }}" class="btn btn-outline-light btn-sm">Šihterica</a>
                <a href="{{ route('organization.schedule.index', $organization->slug) }}" class="btn btn-outline-light btn-sm">Raspored</a>
                <a href="{{ route('organization.exceptions.index', $organization->slug) }}" class="btn btn-outline-light btn-sm">Iznimke</a>
            @endif
            @if(auth()->user() && app(\App\Services\OrganizationRbacService::class)->can($organization->id, auth()->id(), 'team.manage'))
                <a href="{{ route('organization.team.index', $organization->slug) }}" class="btn btn-outline-light btn-sm">Tim</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-light btn-sm" type="submit">Odjava</button>
            </form>
        </div>
    </div>
</nav>
<div class="container py-4">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @yield('content')
</div>
</body>
</html>
