<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'bio',
        'website',
        'rating',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
