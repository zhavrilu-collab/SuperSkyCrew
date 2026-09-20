<div class="org-shema org-shema-poslovna">
    <ul>
        @forelse($enterpriseForest as $unit)
            @include('organization.structure.enterprise-node', ['unit' => $unit])
        @empty
            <li>
                <div class="org-kutija org-kutija-root">
                    <div class="org-kutija-kapa">{{ $organization->name }}</div>
                    <div class="org-kutija-tijelo"><span>Nema poslovnih jedinica na taj dan.</span></div>
                </div>
            </li>
        @endforelse
    </ul>
</div>
