<?php

namespace App\Models;

use App\Enums\JobStatus;
use App\Enums\JobType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Job extends Model
{
    use SoftDeletes;

    protected $table = 'posted_jobs';

    protected $fillable = [
        'employer_id',
        'category_id',
        'title',
        'description',
        'budget',
        'type',
        'status',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'type' => JobType::class,
        'status' => JobStatus::class,
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'category_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skills', 'job_id', 'skill_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(JobAttachment::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
