<?php

namespace App\Models;

class ReminderSend extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'kind',
        'subject_key',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}
