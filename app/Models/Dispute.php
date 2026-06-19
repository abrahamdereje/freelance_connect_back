<?php

namespace App\Models;

use App\Enums\DisputeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    protected $fillable = [
        'contract_id',
        'milestone_id',
        'raiser_id',
        'reason',
        'description',
        'evidence_path',
        'status',
        'resolution_details',
        'resolved_by',
    ];

    protected $casts = [
        'status' => DisputeStatus::class,
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function raiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raiser_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
