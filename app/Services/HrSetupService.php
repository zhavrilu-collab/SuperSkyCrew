<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Enums\RequestType;
use App\Enums\RetentionClass;
use App\Models\AbsenceCode;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\LeaveTenureRule;
use App\Models\Organization;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Storage;

class HrSetupService
{
    public function provision(Organization $organization): void
    {
        $this->seedAbsenceCodes($organization);
        $this->seedDocumentTypes($organization);
        $this->seedDocumentTemplates($organization);
        $this->seedWorkflows($organization);
        $this->seedLeaveTenureRules($organization);
    }

    private function seedAbsenceCodes(Organization $organization): void
    {
        $codes = [
            ['code' => 'RD', 'name' => 'Redovni rad', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Redovno dnevno radno vrijeme prema rasporedu ili ugovoru.'],
            ['code' => 'NO', 'name' => 'Noćni rad', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Rad između 22:00 i 06:00 (čl. 13. st. 2.).'],
            ['code' => 'PK', 'name' => 'Prekovremeni rad', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Rad iznad ugovorenog dnevnog ili tjednog fonda.'],
            ['code' => 'ND', 'name' => 'Rad nedjeljom', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Rad u nedjelju, neovisno o rasporedu smjene.'],
            ['code' => 'SM', 'name' => 'Smjenski rad', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Rad u smjenama prema kalendaru organizacije.'],
            ['code' => 'DV', 'name' => 'Dvokratni rad', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Rad u dva odvojena dnevna razdoblja.'],
            ['code' => 'TE', 'name' => 'Terenski rad', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Sati terenskog rada (čl. 13. st. 1. t. 7.).'],
            ['code' => 'PP', 'name' => 'Pripravnost', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Sati pripravnosti (čl. 13. st. 1. t. 8.).'],
            ['code' => 'ZA', 'name' => 'Zastoj / prekid', 'category' => 'other', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Prekid krivnjom poslodavca ili okolnostima za koje radnik nije odgovoran.'],
            ['code' => 'GO', 'name' => 'Godišnji odmor', 'category' => 'leave', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => true, 'meaning' => 'Korištenje godišnjeg odmora. Troši fond GO.'],
            ['code' => 'DO', 'name' => 'Dnevni odmor', 'category' => 'leave', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Dnevni odmor između smjena, ako se vodi u šihterici.'],
            ['code' => 'TJ', 'name' => 'Tjedni odmor', 'category' => 'leave', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Tjedni odmor, ako se vodi u šihterici.'],
            ['code' => 'BO', 'name' => 'Privremena nesposobnost (bolovanje)', 'category' => 'sick', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Privremena nesposobnost za rad. Teret poslodavca ili HZZO-a označava se u napomeni.'],
            ['code' => 'PD', 'name' => 'Plaćeni dopust', 'category' => 'leave', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Plaćeni dopust i odsutnost s rada prema internom aktu.'],
            ['code' => 'NP', 'name' => 'Neplaćeni dopust', 'category' => 'leave', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false, 'meaning' => 'Neplaćeni dopust koji ne troši fond GO.'],
            ['code' => 'SK', 'name' => 'Neplaćeni dopust za osobnu skrb', 'category' => 'leave', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false, 'meaning' => 'Neplaćeni dopust za osobnu skrb.'],
            ['code' => 'OO', 'name' => 'Očinski / posvojiteljski dopust', 'category' => 'leave', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Očinski dopust i dopust drugog posvojitelja.'],
            ['code' => 'KD', 'name' => 'Dopust kandidata', 'category' => 'leave', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false, 'meaning' => 'Neplaćeni dopust kandidata (predsjednik, Sabor, županija, grad, općina).'],
            ['code' => 'PO', 'name' => 'Nenazočnost po zahtjevu radnika', 'category' => 'other', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false, 'meaning' => 'Nenazočnost na zahtjev radnika, izvan GO i dopusta.'],
            ['code' => 'KR', 'name' => 'Nenazočnost krivnjom radnika', 'category' => 'other', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false, 'meaning' => 'Nenazočnost krivnjom radnika.'],
            ['code' => 'VO', 'name' => 'Vojna obveza / pričuva', 'category' => 'other', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Vojna obveza ili ugovorna pričuva.'],
            ['code' => 'ST', 'name' => 'Štrajk', 'category' => 'other', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false, 'meaning' => 'Sudjelovanje u štrajku.'],
            ['code' => 'LO', 'name' => 'Lockout', 'category' => 'other', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false, 'meaning' => 'Isključenje s rada (lockout).'],
            ['code' => 'BL', 'name' => 'Blagdan / neradni dan', 'category' => 'holiday', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false, 'meaning' => 'Blagdan ili zakonom utvrđeni neradni dan.'],
        ];

        foreach ($codes as $code) {
            AbsenceCode::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'code' => $code['code'],
                ],
                $code + ['is_system' => true],
            );
        }
    }

    private function seedDocumentTypes(Organization $organization): void
    {
        $types = [
            ['code' => 'uor', 'name' => 'Ugovor o radu', 'retention_class' => RetentionClass::Contract->value, 'tracks_expiry' => true, 'print_key' => 'contract', 'sort_order' => 10],
            ['code' => 'aneks', 'name' => 'Aneks ugovora', 'retention_class' => RetentionClass::Contract->value, 'tracks_expiry' => true, 'print_key' => null, 'sort_order' => 20],
            ['code' => 'rjesenje_go', 'name' => 'Rješenje o GO', 'retention_class' => RetentionClass::Other->value, 'tracks_expiry' => false, 'print_key' => 'leave_decision', 'sort_order' => 30],
            ['code' => 'uputnica', 'name' => 'Uputnica za liječnički pregled', 'retention_class' => RetentionClass::Safety->value, 'tracks_expiry' => false, 'print_key' => 'referral', 'sort_order' => 40],
            ['code' => 'dozvola', 'name' => 'Dozvola / boravište', 'retention_class' => RetentionClass::Other->value, 'tracks_expiry' => true, 'print_key' => null, 'sort_order' => 50],
            ['code' => 'certifikat', 'name' => 'Certifikat / atest', 'retention_class' => RetentionClass::Education->value, 'tracks_expiry' => true, 'print_key' => null, 'sort_order' => 60],
            ['code' => 'porezna', 'name' => 'Porezna kartica', 'retention_class' => RetentionClass::Payroll->value, 'tracks_expiry' => false, 'print_key' => null, 'sort_order' => 70],
            ['code' => 'pisani_pregled', 'name' => 'Pisani pregled (čl. 4.)', 'retention_class' => RetentionClass::WrittenReview->value, 'tracks_expiry' => false, 'print_key' => 'review', 'sort_order' => 80],
        ];

        foreach ($types as $type) {
            DocumentType::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'code' => $type['code'],
                ],
                $type + ['is_system' => true],
            );
        }
    }

    private function seedDocumentTemplates(Organization $organization): void
    {
        $fill = app(DocumentFillService::class);

        foreach ($fill->systemSpecs() as $spec) {
            $type = DocumentType::query()
                ->where('organization_id', $organization->id)
                ->where('code', $spec['type_code'])
                ->first();
            $path = 'document-templates/'.$organization->id.'/'.$spec['file'];
            Storage::disk('local')->put($path, $fill->systemDocx($spec['kind']));

            DocumentTemplate::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'kind' => $spec['kind'],
                ],
                [
                    'document_type_id' => $type?->id,
                    'name' => $spec['name'],
                    'file_path' => $path,
                    'original_name' => $spec['file'],
                    'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'is_system' => true,
                ],
            );
        }
    }

    private function seedLeaveTenureRules(Organization $organization): void
    {
        if (LeaveTenureRule::query()->where('organization_id', $organization->id)->exists()) {
            return;
        }

        foreach ([
            ['min_years' => 10, 'extra_days' => 2],
            ['min_years' => 20, 'extra_days' => 4],
        ] as $rule) {
            LeaveTenureRule::query()->create([
                'organization_id' => $organization->id,
                'min_years' => $rule['min_years'],
                'extra_days' => $rule['extra_days'],
            ]);
        }
    }

    private function seedWorkflows(Organization $organization): void
    {
        foreach (RequestType::cases() as $type) {
            $workflow = Workflow::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'type' => $type->value,
                ],
                [
                    'name' => $type->label(),
                    'is_active' => true,
                ],
            );

            if ($workflow->steps()->doesntExist()) {
                $this->seedDefaultSteps($organization, $workflow, $type);
            }
        }
    }

    private function seedDefaultSteps(Organization $organization, Workflow $workflow, RequestType $type): void
    {
        $steps = match ($type) {
            RequestType::LeaveAnnual => [
                ['role' => OrganizationRole::Manager->value, 'max_days' => 3, 'min_days' => null],
                ['role' => OrganizationRole::Hr->value, 'max_days' => null, 'min_days' => 4],
            ],
            RequestType::Overtime, RequestType::PunchCorrection, RequestType::ShiftSwap => [
                ['role' => OrganizationRole::Manager->value, 'max_days' => null, 'min_days' => null],
            ],
            default => [
                ['role' => OrganizationRole::Hr->value, 'max_days' => null, 'min_days' => null],
            ],
        };

        foreach ($steps as $index => $step) {
            WorkflowStep::query()->create([
                'organization_id' => $organization->id,
                'workflow_id' => $workflow->id,
                'position' => $index + 1,
                'role' => $step['role'],
                'min_days' => $step['min_days'],
                'max_days' => $step['max_days'],
            ]);
        }
    }
}
