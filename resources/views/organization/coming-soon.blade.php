@extends('layouts.organization')

@section('title', $title)
@section('nav-suffix', $nav)

@section('content')
<div class="page-heading">
    <h1>{{ $title }}</h1>
    <p class="text-muted mb-0">{{ $lead }}</p>
</div>

<div class="kartica-kontejner">
    <p class="fw-semibold text-tema mb-2">Uskoro</p>
    <p class="mb-0">Ovaj modul još nije spreman. Stavka je u izborniku da se vidi mjesto u ustroju aplikacije; postojeći ekrani (dosjei, ugovori na kartici osobe, predlošci dokumenata) ostaju na snazi.</p>
</div>
@endsection
