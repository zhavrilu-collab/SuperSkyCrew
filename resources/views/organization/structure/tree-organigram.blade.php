<div class="org-shema org-shema-ljudi">
    <ul>
        @forelse($chartForest as $person)
            @include('organization.structure.person-node', ['person' => $person])
        @empty
            <li>
                <div class="org-kutija org-kutija-osoba">
                    <div class="org-kutija-osoba-head">
                        <span class="org-inicijali">—</span>
                        <strong>Nema osoba</strong>
                    </div>
                    <div class="org-kutija-osoba-redovi">Nema kadra za odabrani filter.</div>
                </div>
            </li>
        @endforelse
    </ul>
</div>
