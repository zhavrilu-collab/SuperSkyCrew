@php
    $jobs = $positions->where('department_id', $department->id);
@endphp
<li>
    <div class="org-kutija org-kutija-mjesto">
        <div class="org-kutija-kapa">{{ $department->name }}</div>
        <div class="org-kutija-tijelo">
            @forelse($jobs as $job)
                <strong>{{ $job->name }}</strong>
            @empty
                <span>Nema radnih mjesta</span>
            @endforelse
        </div>
        <div class="org-kutija-akcije">
            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="modal" data-bs-target="#modal-mjesto" data-department="{{ $department->id }}">Novo RM</button>
            <a class="btn btn-sm btn-light" href="{{ $ustrojUrl('organigram', $on->toDateString(), null, false, $department->enterprise_unit_id, $department->id) }}">Organigram</a>
        </div>
    </div>
    @if($department->children->isNotEmpty())
        <ul>
            @foreach($department->children as $child)
                @include('organization.structure.job-node', ['department' => $child])
            @endforeach
        </ul>
    @endif
</li>
