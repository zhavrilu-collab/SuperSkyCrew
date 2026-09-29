@if(\App\Support\ImpersonationSession::isActive())
    <div class="alert alert-warning rounded-0 mb-0 py-2 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="small mb-0">
            Impersonacija — {{ \App\Support\ImpersonationSession::adminLabel() ?? 'administrator platforme' }}
        </span>
        <form method="POST" action="{{ route('impersonation.exit') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-dark">Završi impersonaciju</button>
        </form>
    </div>
@endif
