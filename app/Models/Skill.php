<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function freelancerProfiles(): BelongsToMany
    {
        return $this->belongsToMany(FreelancerProfile::class, 'freelancer_skills', 'skill_id', 'freelancer_profile_id');
    }

    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_skills', 'skill_id', 'job_id');
    }
}
