<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Models\Contract;
use App\Models\Milestone;
use App\Models\Proposal;
use App\Models\Job;
use App\Enums\ContractStatus;
use App\Enums\MilestoneStatus;
use App\Enums\ProposalStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends ApiController
{
    public function employerStats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Total number of jobs posted by this employer
        $jobsPosted = Job::where('employer_id', $user->id)->count();

        // Active contracts where this user is employer
        $activeContracts = Contract::where('employer_id', $user->id)
            ->where('status', ContractStatus::ACTIVE)
            ->count();

        // Total proposals received across all their jobs
        $proposalsReceived = Proposal::whereHas('job', function ($q) use ($user) {
            $q->where('employer_id', $user->id);
        })->count();

        // Total amount spent (released milestones on employer's contracts)
        $totalSpent = Milestone::whereHas('contract', function ($q) use ($user) {
            $q->where('employer_id', $user->id);
        })->where('status', MilestoneStatus::RELEASED)->sum('amount');

        return $this->successResponse([
            'jobs_posted'         => $jobsPosted,
            'active_contracts'    => $activeContracts,
            'proposals_received'  => $proposalsReceived,
            'total_spent'         => (float) $totalSpent,
        ], 'Employer stats retrieved successfully.');
    }

    public function freelancerStats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Total number of contracts for this freelancer
        $totalContracts = Contract::where('freelancer_id', $user->id)->count();

        // Active bids (proposals still pending)
        $activeBids = Proposal::where('freelancer_id', $user->id)
            ->where('status', ProposalStatus::PENDING)
            ->count();

        // Total earned (released milestones on freelancer's contracts)
        $totalEarned = Milestone::whereHas('contract', function ($q) use ($user) {
            $q->where('freelancer_id', $user->id);
        })->where('status', MilestoneStatus::RELEASED)->sum('amount');

        // Completed contracts count
        $completedContracts = Contract::where('freelancer_id', $user->id)
            ->where('status', ContractStatus::COMPLETED)
            ->count();

        return $this->successResponse([
            'total_contracts'     => $totalContracts,
            'active_bids'         => $activeBids,
            'total_earned'        => (float) $totalEarned,
            'completed_contracts' => $completedContracts,
        ], 'Freelancer stats retrieved successfully.');
    }
}
