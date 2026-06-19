<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FreelancerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'bio',
        'hourly_rate',
        'rating',
        'portfolio',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'rating' => 'decimal:2',
        'portfolio' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'freelancer_skills', 'freelancer_profile_id', 'skill_id');
    }
}
