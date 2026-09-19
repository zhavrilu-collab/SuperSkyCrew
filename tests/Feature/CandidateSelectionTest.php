<?php

namespace Tests\Feature;

use App\Enums\InterviewOutcome;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Models\InterviewNote;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_uploads_cv_and_interview_note(): void
    {
        Storage::fake('local');
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $candidate = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Marta',
            'last_name' => 'Kandidat',
            'status' => PersonStatus::Candidate,
            'job_title' => 'Pripravnica',
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.index', [$organization->slug, 'status' => PersonStatus::Candidate->value]))
            ->assertOk()
            ->assertSee('Marta Kandidat')
            ->assertSee('nema CV')
            ->assertSee('razgovori 0');

        $this->actingAs($owner)
            ->get(route('organization.people.edit', [$organization->slug, $candidate, 'tab' => 'odabir']))
            ->assertOk()
            ->assertSee('Odabir')
            ->assertSee('Bilješke razgovora')
            ->assertSee('Još nema priloženog CV-a')
            ->assertSee('kartica-kontejner', false);

        $cv = UploadedFile::fake()->create('marta-cv.pdf', 40, 'application/pdf');
        $this->actingAs($owner)
            ->post(route('organization.people.cv.store', [$organization->slug, $candidate]), [
                'cv' => $cv,
            ])
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $candidate, 'tab' => 'odabir']));

        $candidate->refresh();
        $this->assertTrue($candidate->hasCv());
        $this->assertSame('marta-cv.pdf', $candidate->cv_original_name);
        Storage::disk('local')->assertExists($candidate->cv_path);

        $this->actingAs($owner)
            ->get(route('organization.people.cv.download', [$organization->slug, $candidate]))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($owner)
            ->post(route('organization.people.notes.store', [$organization->slug, $candidate]), [
                'occurred_on' => '2026-09-18',
                'interviewer_user_id' => $owner->id,
                'outcome' => InterviewOutcome::Positive->value,
                'body' => 'Jaka komunikacija, preporuka za UOR.',
            ])
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $candidate, 'tab' => 'odabir']));

        $this->assertSame(1, InterviewNote::query()->where('person_id', $candidate->id)->count());

        $this->actingAs($owner)
            ->get(route('organization.people.edit', [$organization->slug, $candidate, 'tab' => 'odabir']))
            ->assertOk()
            ->assertSee('marta-cv.pdf')
            ->assertSee('Preporuka')
            ->assertSee('Jaka komunikacija');

        $this->actingAs($owner)
            ->get(route('organization.people.index', [$organization->slug, 'status' => 'candidate']))
            ->assertOk()
            ->assertSee('CV priložen')
            ->assertSee('razgovori 1');

        $this->actingAs($owner)
            ->get(route('organization.people.edit', [$organization->slug, $candidate]))
            ->assertOk()
            ->assertSee('>da<', false)
            ->assertSee('Razgovori');
    }

    public function test_cv_and_notes_survive_hire_and_employee_is_forbidden(): void
    {
        Storage::fake('local');
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);
        $candidate = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Odabir',
            'status' => PersonStatus::Candidate,
        ]);
        $this->actingAs($owner)
            ->post(route('organization.people.cv.store', [$organization->slug, $candidate]), [
                'cv' => UploadedFile::fake()->create('iva.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect();
        $this->actingAs($owner)
            ->post(route('organization.people.notes.store', [$organization->slug, $candidate]), [
                'occurred_on' => '2026-09-10',
                'outcome' => InterviewOutcome::Held->value,
                'body' => 'Prvi krug.',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('organization.people.hire', [$organization->slug, $candidate]))
            ->assertRedirect();

        $candidate->refresh();
        $this->assertSame(PersonStatus::Employee, $candidate->status);
        $this->assertTrue($candidate->hasCv());
        $this->assertSame(1, $candidate->interviewNotes()->count());

        $this->actingAs($owner)
            ->get(route('organization.people.edit', [$organization->slug, $candidate, 'tab' => 'odabir']))
            ->assertOk()
            ->assertSee('iva.pdf')
            ->assertSee('Prvi krug');

        $note = $candidate->interviewNotes()->first();
        $this->actingAs($employee)
            ->get(route('organization.people.edit', [$organization->slug, $candidate, 'tab' => 'odabir']))
            ->assertForbidden();
        $this->actingAs($employee)
            ->get(route('organization.people.cv.download', [$organization->slug, $candidate]))
            ->assertForbidden();
        $this->actingAs($employee)
            ->delete(route('organization.people.notes.destroy', [$organization->slug, $candidate, $note]))
            ->assertForbidden();
    }

    public function test_new_candidate_opens_selection_tab(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $response = $this->actingAs($owner)
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Lana',
                'last_name' => 'Novi',
                'status' => PersonStatus::Candidate->value,
            ]);
        $person = Person::query()->where('last_name', 'Novi')->first();
        $this->assertNotNull($person);
        $response->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'odabir']));
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Odabir d.o.o.',
            'slug' => 'odabir-firma',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $organization];
    }
}
