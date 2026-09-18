<?php

namespace App\Services;

use App\Enums\RequestType;
use App\Models\AbsenceCode;
use App\Models\Organization;
use App\Models\Workflow;

class HrSetupService
{
    public function provision(Organization $organization): void
    {
        $this->seedAbsenceCodes($organization);
        $this->seedWorkflows($organization);
    }

    private function seedAbsenceCodes(Organization $organization): void
    {
        $codes = [
            ['code' => 'RD', 'name' => 'Redovni rad', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false],
            ['code' => 'NO', 'name' => 'Noćni rad', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false],
            ['code' => 'PK', 'name' => 'Prekovremeni rad', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false],
            ['code' => 'ND', 'name' => 'Rad nedjeljom', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false],
            ['code' => 'SM', 'name' => 'Smjenski rad', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false],
            ['code' => 'DV', 'name' => 'Dvokratni rad', 'category' => 'presence', 'kind' => 'presence', 'paid' => true, 'consumes_annual_leave' => false],
            ['code' => 'GO', 'name' => 'Godišnji odmor', 'category' => 'leave', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => true],
            ['code' => 'BO', 'name' => 'Privremena nesposobnost (bolovanje)', 'category' => 'sick', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false],
            ['code' => 'PD', 'name' => 'Plaćeni dopust', 'category' => 'leave', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false],
            ['code' => 'NP', 'name' => 'Neplaćeni dopust', 'category' => 'leave', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false],
            ['code' => 'SK', 'name' => 'Neplaćeni dopust za osobnu skrb', 'category' => 'leave', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false],
            ['code' => 'OO', 'name' => 'Očinski / posvojiteljski dopust', 'category' => 'leave', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false],
            ['code' => 'PO', 'name' => 'Nenazočnost po zahtjevu radnika', 'category' => 'other', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false],
            ['code' => 'KR', 'name' => 'Nenazočnost krivnjom radnika', 'category' => 'other', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false],
            ['code' => 'VO', 'name' => 'Vojna obveza / pričuva', 'category' => 'other', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false],
            ['code' => 'ST', 'name' => 'Štrajk', 'category' => 'other', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false],
            ['code' => 'LO', 'name' => 'Lockout', 'category' => 'other', 'kind' => 'absence', 'paid' => false, 'consumes_annual_leave' => false],
            ['code' => 'BL', 'name' => 'Blagdan / neradni dan', 'category' => 'holiday', 'kind' => 'absence', 'paid' => true, 'consumes_annual_leave' => false],
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

    private function seedWorkflows(Organization $organization): void
    {
        foreach (RequestType::cases() as $type) {
            Workflow::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'type' => $type->value,
                ],
                [
                    'name' => $type->label(),
                    'is_active' => true,
                ],
            );
        }
    }
}
