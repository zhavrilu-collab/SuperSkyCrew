@php
    $jobs = $positions->where('department_id', $department->id);
@endphp
<li>
    <div class="org-kutija-grupa">
        @forelse($jobs as $job)
            <div class="org-kutija org-kutija-mjesto">
                <div class="org-kutija-kapa">{{ $job->name }}</div>
                <div class="org-kutija-tijelo">
                    <span>{{ $department->name }}</span>
                    <strong>{{ $job->rad1g ?: 'bez šifre' }}</strong>
                </div>
                <div class="org-kutija-akcije">
                    <button class="org-kutija-akcija" type="button" data-bs-toggle="modal" data-bs-target="#modal-mjesto" data-department="{{ $department->id }}" title="Novo RM">+</button>
                    <button class="org-kutija-akcija" type="button"
                        data-bs-toggle="modal" data-bs-target="#modal-mjesto"
                        data-ustroj-edit="mjesto"
                        data-action="{{ route('organization.structure.positions.update', [$organization->slug, $job]) }}"
                        data-name="{{ $job->name }}"
                        data-department="{{ $job->department_id }}"
                        data-rad1g="{{ $job->rad1g }}"
                        data-go="{{ $job->annual_leave_days }}"
                        data-description="{{ $job->description }}"
                        data-from="{{ $job->valid_from?->toDateString() }}"
                        data-to="{{ $job->valid_to?->toDateString() }}"
                        title="Uredi">✎</button>
                    <a class="org-kutija-akcija" href="{{ $ustrojUrl('organigram', $on->toDateString(), null, false, $department->enterprise_unit_id, $department->id) }}" title="Organizacijska shema">↗</a>
                </div>
            </div>
        @empty
            <div class="org-kutija org-kutija-mjesto">
                <div class="org-kutija-kapa">{{ $department->name }}</div>
                <div class="org-kutija-tijelo"><span>Nema radnih mjesta</span></div>
                <div class="org-kutija-akcije">
                    <button class="org-kutija-akcija" type="button" data-bs-toggle="modal" data-bs-target="#modal-mjesto" data-department="{{ $department->id }}" title="Novo RM">+</button>
                </div>
            </div>
        @endforelse
    </div>
    @if($department->children->isNotEmpty())
        <ul>
            @foreach($department->children as $child)
                @include('organization.structure.job-node', ['department' => $child])
            @endforeach
        </ul>
    @endif
</li>
