<li>
    <a class="org-kutija org-kutija-osoba text-decoration-none" href="{{ route('organization.people.edit', [$organization->slug, $person]) }}">
        <div class="org-kutija-osoba-head">
            <span class="org-inicijali" aria-hidden="true">{{ $person->initials() }}</span>
            <strong>{{ $person->fullName() }}</strong>
        </div>
        <div class="org-kutija-osoba-redovi">
            <div>🏢 {{ $person->department?->name ?: '—' }}</div>
            <div>💼 {{ $person->jobLabel() }}</div>
            <div>✉ {{ $person->user?->email ?: '—' }}</div>
        </div>
    </a>
    @if($person->reports->isNotEmpty())
        <ul>
            @foreach($person->reports as $report)
                @include('organization.structure.person-node', ['person' => $report])
            @endforeach
        </ul>
    @endif
</li>
