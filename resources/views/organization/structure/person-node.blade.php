<li>
    <a class="org-kutija org-kutija-osoba text-decoration-none" href="{{ route('organization.people.edit', [$organization->slug, $person]) }}">
        <div class="org-kutija-kapa">{{ $person->fullName() }}</div>
        <div class="org-kutija-tijelo org-kutija-osoba-tijelo">
            <span class="org-inicijali" aria-hidden="true">{{ $person->initials() }}</span>
            <span>
                {{ $person->department?->name ?: '—' }}<br>
                {{ $person->jobLabel() }}<br>
                {{ $person->user?->email ?: '—' }}
            </span>
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
