<div class="kartica-kontejner">
    <div class="forma-sekcija mt-0 pt-0 border-0">
        <h2>Povijest angažmana</h2>
        <p>Svaka izmjena statusa, odjela, radnog mjesta, lokacije ili mjesta troška zatvara prethodno razdoblje. Matična knjiga čita raspored na odabrani dan.</p>
    </div>
    <div class="table-responsive table-responsive-no-sticky">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Razdoblje</th>
                    <th>Status</th>
                    <th>Odjel</th>
                    <th>Radno mjesto</th>
                    <th>Lokacija</th>
                    <th>MT</th>
                    <th>Unio</th>
                </tr>
            </thead>
            <tbody>
                @forelse($person->engagements as $row)
                    <tr>
                        <td>{{ $row->periodLabel() }}{{ $row->isCurrent() ? ' · važeće' : '' }}</td>
                        <td>{{ $row->status->label() }}</td>
                        <td>{{ $row->department?->name ?: '—' }}</td>
                        <td>{{ $row->jobLabel() }}</td>
                        <td>{{ $row->location?->name ?: '—' }}</td>
                        <td>{{ $row->costCenter?->summary() ?: '—' }}</td>
                        <td>{{ $row->changedByUser?->name ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">Još nema zabilježenih razdoblja.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
