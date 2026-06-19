<?php

namespace App\Services;

use App\DTOs\JobDTO;
use App\Models\Job;
use App\Repositories\Contracts\JobRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class JobService
{
    public function __construct(
        protected JobRepositoryInterface $jobRepository
    ) {}

    public function createJob(int $employerId, JobDTO $dto): Job
    {
        return DB::transaction(function () use ($employerId, $dto) {
            $job = $this->jobRepository->create([
                'employer_id' => $employerId,
                'category_id' => $dto->category_id,
                'title' => $dto->title,
                'description' => $dto->description,
                'budget' => $dto->budget,
                'type' => $dto->type,
                'status' => \App\Enums\JobStatus::OPEN,
            ]);

            if (!empty($dto->skills)) {
                $job->skills()->sync($dto->skills);
            }

            if (!empty($dto->attachments)) {
                foreach ($dto->attachments as $file) {
                    if ($file instanceof UploadedFile) {
                        $path = $file->store('job_attachments', 'public');
                        $job->attachments()->create([
                            'file_name' => $file->getClientOriginalName(),
                            'file_path' => $path,
                            'file_size' => $file->getSize(),
                        ]);
                    }
                }
            }

            return $job;
        });
    }

    public function updateJob(Job $job, JobDTO $dto): Job
    {
        return DB::transaction(function () use ($job, $dto) {
            $this->jobRepository->update($job, [
                'category_id' => $dto->category_id,
                'title' => $dto->title,
                'description' => $dto->description,
                'budget' => $dto->budget,
                'type' => $dto->type,
            ]);

            $job->skills()->sync($dto->skills);

            if (!empty($dto->attachments)) {
                foreach ($dto->attachments as $file) {
                    if ($file instanceof UploadedFile) {
                        $path = $file->store('job_attachments', 'public');
                        $job->attachments()->create([
                            'file_name' => $file->getClientOriginalName(),
                            'file_path' => $path,
                            'file_size' => $file->getSize(),
                        ]);
                    }
                }
            }

            return $job->fresh(['skills', 'attachments']);
        });
    }

    public function deleteJob(Job $job): bool
    {
        return $this->jobRepository->delete($job);
    }
}
