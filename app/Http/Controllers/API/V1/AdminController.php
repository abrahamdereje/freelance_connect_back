<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Http\Resources\UserResource;
use App\Http\Resources\JobResource;
use App\Models\User;
use App\Models\Dispute;
use App\Models\Job;
use App\Models\Milestone;
use App\Enums\DisputeStatus;
use App\Enums\JobStatus;
use App\Enums\MilestoneStatus;
use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends ApiController
{
    public function stats(Request $request): JsonResponse
    {
        $usersCount = User::where('role', '!=', UserRole::ADMIN)->count();

        $disputesCount = Dispute::whereIn('status', [DisputeStatus::PENDING, DisputeStatus::RESOLVING])->count();

        $openJobsCount = Job::where('status', JobStatus::OPEN)->count();

        // Calculate total escrow by summing funded milestones
        $totalEscrow = Milestone::where('status', MilestoneStatus::FUNDED)->sum('amount');

        return $this->successResponse([
            'users'   => $usersCount,
            'disputes' => $disputesCount,
            'jobs'    => $openJobsCount,
            'escrow'  => (float) $totalEscrow,
        ], 'Admin stats retrieved successfully.');
    }

    public function users(Request $request): JsonResponse
    {
        $query = User::query()->where('role', '!=', UserRole::ADMIN);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        $users = $query->latest()->paginate((int) $request->get('per_page', 50));

        return $this->successResponse(
            UserResource::collection($users)->response()->getData(true),
            'Users retrieved successfully.'
        );
    }

    public function jobs(Request $request): JsonResponse
    {
        $query = Job::with(['employer', 'category', 'skills']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $jobs = $query->latest()->paginate((int) $request->get('per_page', 50));

        return $this->successResponse(
            JobResource::collection($jobs)->response()->getData(true),
            'Jobs retrieved successfully.'
        );
    }

    public function suspendUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->isAdmin()) {
            return $this->errorResponse('Cannot suspend another admin.', 403);
        }

        $user->update(['is_suspended' => !$user->is_suspended]);

        return $this->successResponse(
            new UserResource($user),
            $user->is_suspended ? 'User suspended.' : 'User activated.'
        );
    }
}
