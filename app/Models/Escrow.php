<?php

namespace App\Models;

use App\Enums\EscrowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Escrow extends Model
{
    protected $fillable = [
        'contract_id',
        'milestone_id',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => EscrowStatus::class,
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }
}
