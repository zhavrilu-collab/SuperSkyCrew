<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\Organization;
use App\Models\PersonDocument;
use App\Models\Punch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RetentionService
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}
    public function proposeDue(Organization $organization, ?Carbon $on = null): int
    {
        $on = ($on ?? now()->timezone(config('app.timezone')))->copy()->startOfDay();
        $count = 0;

        $documents = PersonDocument::query()
            ->forOrganization($organization)
            ->with(['person', 'documentType'])
            ->whereNull('disposed_at')
            ->get();

        foreach ($documents as $document) {
            $until = $this->retainUntilFor($document);
            if ($until === null) {
                continue;
            }

            $document->retain_until = $until->toDateString();

            if ($on->gt($until) && $document->retention_proposed_at === null) {
                $document->retention_proposed_at = now();
                $count++;
            }

            if ($document->isDirty()) {
                $document->save();
            }
        }

        return $count;
    }

    /**
     * @return Collection<int, PersonDocument>
     */
    public function proposed(Organization $organization): Collection
    {
        return PersonDocument::query()
            ->forOrganization($organization)
            ->with(['person', 'documentType'])
            ->whereNull('disposed_at')
            ->whereNotNull('retention_proposed_at')
            ->orderBy('retain_until')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, PersonDocument>
     */
    public function recentlyDisposed(Organization $organization, int $limit = 20): Collection
    {
        return PersonDocument::query()
            ->forOrganization($organization)
            ->with(['person', 'documentType'])
            ->whereNotNull('disposed_at')
            ->orderByDesc('disposed_at')
            ->limit($limit)
            ->get();
    }

    public function confirm(PersonDocument $document, User $actor): void
    {
        if ($document->disposed_at) {
            return;
        }

        $document->deleteFile();
        $document->file_path = null;
        $document->original_name = null;
        $document->mime = null;
        $document->disposed_at = now();
        $document->disposed_by = $actor->id;
        $document->save();

        $organization = $document->organization ?? Organization::query()->find($document->organization_id);
        if ($organization !== null) {
            $this->audit->record(
                $organization,
                AuditAction::RetentionDispose,
                $actor,
                'Obrisana datoteka dosjea: '.$document->label(),
                $document->person,
                PersonDocument::class,
                $document->id,
            );
        }
    }

    public function retainUntilFor(PersonDocument $document): ?Carbon
    {
        $class = $document->documentType?->retention_class;
        if ($class === null) {
            return null;
        }

        $origin = $document->issued_on?->copy() ?? $document->created_at?->copy();
        if ($origin === null) {
            return null;
        }

        $until = $class->retainUntil($document->person?->ended_at, $origin);

        return $until?->copy()->startOfDay();
    }

    public function purgePunchPhotos(int $days = 30): int
    {
        $cutoff = now()->subDays($days);
        $count = 0;
        Punch::query()
            ->whereNotNull('photo_path')
            ->where('photo_taken_at', '<', $cutoff)
            ->orderBy('id')
            ->each(function (Punch $punch) use (&$count): void {
                $punch->deletePhoto();
                $count++;
            });

        return $count;
    }
}
