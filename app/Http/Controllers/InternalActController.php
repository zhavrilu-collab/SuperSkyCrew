<?php

namespace App\Http\Controllers;

use App\Enums\InternalActKind;
use App\Models\InternalAct;
use App\Services\OrganizationRbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InternalActController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function index(): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        return view('organization.systematization.acts', [
            'organization' => $organization,
            'acts' => InternalAct::query()->forOrganization($organization)->orderByDesc('published_at')->orderByDesc('id')->get(),
            'kinds' => InternalActKind::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(InternalActKind::class)],
            'version' => ['nullable', 'string', 'max:32'],
            'published_at' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'extensions:pdf,doc,docx', 'max:8192'],
        ]);

        $path = null;
        $original = null;
        $mime = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('internal-acts/'.$organization->id, 'local');
            $original = $file->getClientOriginalName();
            $mime = $file->getClientMimeType();
        }

        InternalAct::query()->create([
            'organization_id' => $organization->id,
            'title' => $data['title'],
            'kind' => $data['kind'],
            'version' => $data['version'] ?? null,
            'must_read' => $request->boolean('must_read'),
            'published_at' => $data['published_at'] ?? now()->toDateString(),
            'file_path' => $path,
            'original_name' => $original,
            'mime' => $mime,
        ]);

        return back()->with('status', 'Interni akt je spremljen.');
    }

    public function download(string $slug, InternalAct $act): StreamedResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($act->organization_id === $organization->id, 404);
        abort_unless($act->hasFile() && Storage::disk('local')->exists($act->file_path), 404);

        return Storage::disk('local')->download($act->file_path, $act->original_name ?: 'akt');
    }

    public function destroy(string $slug, InternalAct $act): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($act->organization_id === $organization->id, 404);
        $act->deleteFile();
        $act->delete();

        return back()->with('status', 'Interni akt je uklonjen.');
    }
}
