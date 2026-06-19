<?php

namespace App\Models;

use App\Enums\MilestoneStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Milestone extends Model
{
    protected $fillable = [
        'contract_id',
        'title',
        'amount',
        'status',
        'due_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => MilestoneStatus::class,
        'due_date' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function escrow(): HasOne
    {
        return $this->hasOne(Escrow::class);
    }
}
