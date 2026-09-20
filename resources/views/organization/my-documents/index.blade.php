@extends('layouts.organization')

@section('title', 'Moji dokumenti')
@section('nav-suffix', 'Moje')

@section('content')
<div class="page-heading">
    <h1>Moji dokumenti</h1>
    <p class="text-muted mb-0">Akti iz dosjea koje HR spremi u vašu kutiju.</p>
</div>

<div class="kartica-kontejner p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Dokument</th>
                    <th>Izdano</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $document)
                    <tr>
                        <td>{{ $document->label() }}</td>
                        <td>{{ $document->issued_on?->format('d.m.Y.') ?: '—' }}</td>
                        <td class="text-end">
                            @if($document->hasFile())
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('organization.my-documents.download', [$organization->slug, $document]) }}">Preuzmi</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted">Kutija je prazna.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
