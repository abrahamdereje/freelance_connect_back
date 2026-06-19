<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAttachment extends Model
{
    protected $fillable = [
        'job_id',
        'file_name',
        'file_path',
        'file_size',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
