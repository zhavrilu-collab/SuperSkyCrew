<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonDocument;
use App\Models\WorkflowRequest;
use App\Support\DocumentMergeFields;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DocumentFillService
{
    public function __construct(
        private readonly RetentionService $retention,
    ) {}

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    public function replacements(Organization $organization, Person $person, array $extra = []): array
    {
        $person->loadMissing(['department', 'location', 'jobPosition', 'costCenter', 'employmentContracts']);
        $contract = $person->currentContract();

        $values = [
            '{{organizacija}}' => $organization->name,
            '{{organizacija_oib}}' => $organization->oib ?: '',
            '{{grad}}' => $organization->city ?: '',
            '{{ime}}' => $person->first_name,
            '{{prezime}}' => $person->last_name,
            '{{ime_prezime}}' => $person->fullName(),
            '{{oib}}' => $person->oib ?: '',
            '{{spol}}' => $person->genderLabel(),
            '{{rodjenje}}' => $person->date_of_birth?->format('d.m.Y.') ?: '',
            '{{drzavljanstvo}}' => $person->citizenship ?: '',
            '{{prebivaliste}}' => $person->residence ?: '',
            '{{status}}' => $person->status->label(),
            '{{radno_mjesto}}' => $person->jobLabel() === '—' ? '' : $person->jobLabel(),
            '{{rad1g}}' => $person->jobPosition?->rad1g ?: '',
            '{{odjel}}' => $person->department?->name ?: '',
            '{{lokacija}}' => $person->location?->name ?: '',
            '{{mt}}' => $person->costCenter?->name ?: '',
            '{{ugovor}}' => ($contract?->contract_type ?? $person->contract_type)?->label() ?: '',
            '{{broj_ugovora}}' => $contract?->number ?: '',
            '{{tjedni_sati}}' => (string) ($contract?->weekly_hours ?: 40),
            '{{probni}}' => $contract?->trial_ends_at?->format('d.m.Y.') ?: '',
            '{{pocetak}}' => ($contract?->starts_at ?? $person->started_at)?->format('d.m.Y.') ?: '',
            '{{prestanak}}' => ($contract?->ends_at ?? $person->ended_at)?->format('d.m.Y.') ?: '',
            '{{fond_go}}' => (string) ($person->annual_leave_days ?: ''),
            '{{djeca}}' => (string) ($person->children_count ?: 0),
            '{{iban}}' => $person->iban ?: '',
            '{{ustupitelj}}' => $person->host_employer ?: '',
            '{{znr}}' => $person->znr_exam_required ? 'da' : 'ne',
            '{{lijecnicki}}' => $person->medical_expires_at?->format('d.m.Y.') ?: '',
            '{{dozvola}}' => $person->work_permit_expires_at?->format('d.m.Y.') ?: '',
            '{{datum}}' => now()->timezone(config('app.timezone'))->format('d.m.Y.'),
            '{{go_broj}}' => '',
            '{{go_od}}' => '',
            '{{go_do}}' => '',
            '{{go_dani}}' => '',
            '{{go_datumi}}' => '',
            '{{go_godina}}' => '',
            '{{go_preostalo}}' => '',
            '{{go_staro}}' => '',
            '{{go_novo}}' => '',
            '{{potpisnik}}' => '',
        ];

        $values = array_merge($values, $extra);

        foreach ($values as $key => $value) {
            $values[$key] = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        }

        return $values;
    }

    /**
     * @return array<string, string>
     */
    public function extrasFromRequest(WorkflowRequest $request): array
    {
        $decision = $request->payload['decision'] ?? [];
        $from = $request->fromDate();
        $to = $request->toDate();
        $dates = $request->payload['dates'] ?? [];

        return [
            '{{go_broj}}' => $request->decisionNumber(),
            '{{go_od}}' => $from ? Carbon::parse($from)->format('d.m.Y.') : '',
            '{{go_do}}' => $to ? Carbon::parse($to)->format('d.m.Y.') : '',
            '{{go_dani}}' => (string) $request->days(),
            '{{go_datumi}}' => implode(', ', array_map(
                fn ($date) => Carbon::parse($date)->format('d.m.Y.'),
                $dates,
            )),
            '{{go_godina}}' => (string) ($decision['year'] ?? ($from ? Carbon::parse($from)->year : '')),
            '{{go_preostalo}}' => (string) ($decision['remaining'] ?? ''),
            '{{go_staro}}' => (string) ($decision['remaining_old'] ?? ''),
            '{{go_novo}}' => (string) ($decision['remaining_new'] ?? ''),
            '{{potpisnik}}' => (string) ($decision['approver_name'] ?? ''),
        ];
    }

    public function downloadName(DocumentTemplate $template, Person $person): string
    {
        $base = pathinfo($template->original_name, PATHINFO_FILENAME) ?: $template->name;
        $ext = strtolower(pathinfo($template->original_name, PATHINFO_EXTENSION) ?: 'docx');

        return Str::slug($base.'-'.$person->last_name.'-'.$person->first_name, '-', 'hr').'.'.$ext;
    }

    /**
     * @param  array<string, string>  $extra
     */
    public function filledContents(DocumentTemplate $template, Person $person, array $extra = []): string
    {
        $binary = Storage::disk('local')->get($template->file_path);
        if (! is_string($binary) || $binary === '') {
            abort(404, 'Predložak nije pronađen.');
        }

        if (! $template->isDocx()) {
            return $binary;
        }

        $organization = $template->organization ?? $person->organization;

        return $this->replaceInDocx($binary, $this->replacements($organization, $person, $extra));
    }

    /**
     * @param  array<string, string>  $extra
     */
    public function storeFilled(DocumentTemplate $template, Person $person, array $extra = [], ?string $title = null, ?string $note = null): PersonDocument
    {
        $contents = $this->filledContents($template, $person, $extra);
        $name = $this->downloadName($template, $person);
        $path = 'person-documents/'.$person->organization_id.'/'.$person->id.'/'.Str::uuid().'-'.$name;
        Storage::disk('local')->put($path, $contents);

        $typeId = $template->document_type_id
            ?: DocumentType::query()
                ->where('organization_id', $person->organization_id)
                ->orderBy('sort_order')
                ->value('id');
        abort_unless($typeId, 422, 'Predlošku nedostaje vrsta dokumenta.');

        $document = PersonDocument::query()->create([
            'organization_id' => $person->organization_id,
            'person_id' => $person->id,
            'document_type_id' => $typeId,
            'title' => $title ?: $template->name,
            'issued_on' => now()->timezone(config('app.timezone'))->toDateString(),
            'note' => $note,
            'file_path' => $path,
            'original_name' => $name,
            'mime' => $template->mime ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        $until = $this->retention->retainUntilFor($document->load(['person', 'documentType']));
        if ($until !== null) {
            $document->retain_until = $until->toDateString();
            $document->save();
        }

        return $document;
    }

    public function storeLeaveDecision(WorkflowRequest $request, Person $person): ?PersonDocument
    {
        $organization = $request->organization ?? $person->organization;
        if ($organization === null) {
            return null;
        }

        $template = DocumentTemplate::query()
            ->forOrganization($organization)
            ->where('kind', 'leave_decision')
            ->first();

        if ($template === null || ! Storage::disk('local')->exists($template->file_path)) {
            return null;
        }

        return $this->storeFilled(
            $template,
            $person,
            $this->extrasFromRequest($request),
            $request->decisionNumber(),
            'Odobreni zahtjev #'.$request->id,
        );
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function replaceInDocx(string $binary, array $replacements): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fill');
        if ($path === false || file_put_contents($path, $binary) === false) {
            return $binary;
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            @unlink($path);

            return $binary;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || ! preg_match('#^word/(document|header\d+|footer\d+)\.xml$#', $name)) {
                continue;
            }
            $xml = $zip->getFromIndex($i);
            if (! is_string($xml) || $xml === '') {
                continue;
            }
            $zip->addFromString($name, $this->replacePlaceholdersInXml($xml, $replacements));
        }
        $zip->close();

        $filled = file_get_contents($path) ?: $binary;
        @unlink($path);

        return $filled;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function replacePlaceholdersInXml(string $xml, array $replacements): string
    {
        $replaced = preg_replace_callback(
            '/\{\{(?:[^}]|<[^>]*>)*\}\}/u',
            function (array $match) use ($replacements): string {
                $plain = preg_replace('/<[^>]+>/', '', $match[0]) ?? $match[0];
                $inner = str_replace(' ', '', trim($plain, '{} '));
                $key = '{{'.$inner.'}}';

                return $replacements[$key] ?? $match[0];
            },
            $xml,
        );

        $xml = is_string($replaced) ? $replaced : $xml;

        return str_replace(array_keys($replacements), array_values($replacements), $xml);
    }

    /**
     * @return list<array{kind: string, type_code: string, name: string, file: string}>
     */
    public function systemSpecs(): array
    {
        return [
            ['kind' => 'uor', 'type_code' => 'uor', 'name' => 'Ugovor o radu (predložak)', 'file' => 'uor.docx'],
            ['kind' => 'leave_decision', 'type_code' => 'rjesenje_go', 'name' => 'Rješenje o GO (predložak)', 'file' => 'rjesenje-go.docx'],
            ['kind' => 'referral', 'type_code' => 'uputnica', 'name' => 'Uputnica za liječnički (predložak)', 'file' => 'uputnica.docx'],
        ];
    }

    public function systemDocx(string $kind): string
    {
        $paragraphs = match ($kind) {
            'uor' => [
                'UGOVOR O RADU',
                '{{organizacija}}, OIB {{organizacija_oib}}, i radnik {{ime_prezime}}, OIB {{oib}}, sklapaju ugovor o radu.',
                'Radno mjesto: {{radno_mjesto}} ({{rad1g}}). Odjel: {{odjel}}. Lokacija: {{lokacija}}.',
                'Vrsta ugovora: {{ugovor}}. Broj: {{broj_ugovora}}. Tjedni sati: {{tjedni_sati}}. Probni rad do: {{probni}}.',
                'Početak rada: {{pocetak}}. Datum izrade: {{datum}}.',
            ],
            'leave_decision' => [
                'RJEŠENJE o korištenju godišnjeg odmora',
                'Klasa: {{go_broj}}. Datum: {{datum}}.',
                'Poslodavac {{organizacija}} odobrava radniku {{ime_prezime}}, OIB {{oib}}, korištenje godišnjeg odmora.',
                'Razdoblje: od {{go_od}} do {{go_do}} ({{go_dani}} radnih dana: {{go_datumi}}).',
                'Nakon ovog korištenja za {{go_godina}}. godinu preostaje {{go_preostalo}} dana (staro {{go_staro}} / novo {{go_novo}}).',
                'Za poslodavca: {{potpisnik}}.',
            ],
            default => [
                'UPUTNICA ZA LIJEČNIČKI PREGLED',
                '{{organizacija}} upućuje radnika {{ime_prezime}}, OIB {{oib}}, na liječnički pregled.',
                'Radno mjesto: {{radno_mjesto}}. Lokacija: {{lokacija}}.',
                'Obavezan ZNR pregled: {{znr}}. Istek važećeg liječničkog: {{lijecnicki}}.',
                'Datum: {{datum}}.',
            ],
        };

        return $this->buildDocx($paragraphs);
    }

    /**
     * @param  list<string>  $paragraphs
     */
    public function buildDocx(array $paragraphs): string
    {
        $body = '';
        foreach ($paragraphs as $paragraph) {
            $body .= '<w:p><w:r><w:t xml:space="preserve">'.htmlspecialchars($paragraph, ENT_XML1, 'UTF-8').'</w:t></w:r></w:p>';
        }

        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/></w:sectPr></w:body></w:document>');
        $zip->close();
        $binary = file_get_contents($path) ?: '';
        @unlink($path);

        return $binary;
    }

    public static function sampleDocx(string $body = 'Ugovor {{ime}} {{prezime}} OIB {{oib}}'): string
    {
        return app(self::class)->buildDocx([$body]);
    }

    /**
     * @return list<string>
     */
    public function fieldHints(): array
    {
        return array_keys(DocumentMergeFields::labels());
    }
}
