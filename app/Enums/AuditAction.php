<?php

namespace App\Enums;

enum AuditAction: string
{
    case PunchManual = 'punch.manual';
    case PunchCorrect = 'punch.correct';
    case PeriodLock = 'period.lock';
    case EvidentialCorrect = 'timesheet.evidential';
    case TimesheetExport = 'timesheet.export';
    case InspectionExport = 'inspection.export';
    case PeopleExport = 'people.export';
    case PayrollExport = 'people.payroll_export';
    case ExceptionResolve = 'exception.resolve';
    case Handover = 'handover.create';
    case RetentionDispose = 'retention.dispose';
    case PersonHire = 'person.hire';
    case PeopleImport = 'people.import';
    case RequestApprove = 'request.approve';
    case RequestReject = 'request.reject';

    public function label(): string
    {
        return match ($this) {
            self::PunchManual => 'Ručni unos prijave',
            self::PunchCorrect => 'Korekcija prijave',
            self::PeriodLock => 'Zaključavanje razdoblja',
            self::EvidentialCorrect => 'Evidencijski sati',
            self::TimesheetExport => 'Izvoz šihterice',
            self::InspectionExport => 'Inspekcijski izvoz',
            self::PeopleExport => 'Izvoz kadra',
            self::PayrollExport => 'Izvoz za plaće',
            self::ExceptionResolve => 'Rješavanje iznimke',
            self::Handover => 'Predaja dokumenta',
            self::RetentionDispose => 'Brisanje dosjea',
            self::PersonHire => 'Prijenos kandidata',
            self::PeopleImport => 'Uvoz kadra',
            self::RequestApprove => 'Odobrenje zahtjeva',
            self::RequestReject => 'Odbijanje zahtjeva',
        };
    }
}
