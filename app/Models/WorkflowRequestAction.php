<?php

namespace App\Models;

use App\Enums\RequestActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowRequestAction extends Model
{
    protected $fillable = [
        'workflow_request_id',
        'user_id',
        'action',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'action' => RequestActionType::class,
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(WorkflowRequest::class, 'workflow_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
