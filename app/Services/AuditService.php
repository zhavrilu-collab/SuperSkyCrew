<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditEvent;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AuditService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(
        Organization $organization,
        AuditAction $action,
        ?User $actor,
        string $summary,
        ?Person $person = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $meta = [],
    ): AuditEvent {
        return AuditEvent::query()->create([
            'organization_id' => $organization->id,
            'actor_user_id' => $actor?->id,
            'person_id' => $person?->id,
            'action' => $action->value,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'summary' => $summary,
            'meta' => $meta === [] ? null : $meta,
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * @return Builder<AuditEvent>
     */
    public function queryFor(Organization $organization): Builder
    {
        return AuditEvent::query()
            ->forOrganization($organization)
            ->with(['actor', 'person']);
    }

    /**
     * @return Collection<int, AuditEvent>
     */
    public function recent(Organization $organization, int $limit = 200): Collection
    {
        return $this->queryFor($organization)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
