@extends('layouts.organization')

@section('title', 'Izrada dokumenata')
@section('nav-suffix', 'Zaposlenici')

@section('content')
<div class="page-heading">
    <h1>Izrada dokumenata</h1>
    <p class="text-muted mb-0">Odaberite osobu i predložak. Akt se sprema u dosje i u kutiju zaposlenika.</p>
</div>

<div class="kartica-kontejner">
    @unless($enabled)
        <p class="text-muted mb-0">Word predlošci nisu uključeni u paketu.</p>
    @else
        <form method="POST" action="{{ route('organization.document-creator.store', $organization->slug) }}" class="row g-3">
            @csrf
            <div class="col-md-5">
                <label class="form-label" for="person_id">Osoba</label>
                <select class="form-select" name="person_id" id="person_id" required>
                    <option value="">—</option>
                    @foreach($people as $person)
                        <option value="{{ $person->id }}" @selected((string) old('person_id') === (string) $person->id)>{{ $person->fullName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="template_id">Predložak</label>
                <select class="form-select" name="template_id" id="template_id" required>
                    <option value="">—</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit" @disabled($templates->isEmpty() || $people->isEmpty())>Izradi</button>
            </div>
        </form>
        @if($templates->isEmpty())
            <p class="text-muted small mt-3 mb-0">Nema predložaka. Dodajte ih u Postavke kadra.</p>
        @endif
    @endunless
</div>
@endsection
