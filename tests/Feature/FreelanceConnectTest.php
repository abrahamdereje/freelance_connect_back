<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Job;
use App\Models\Proposal;
use App\Models\Contract;
use App\Models\Milestone;
use App\Models\Dispute;
use App\Models\JobCategory;
use App\Models\Wallet;
use App\Enums\UserRole;
use App\Enums\JobType;
use App\Enums\JobStatus;
use App\Enums\ProposalStatus;
use App\Enums\ContractStatus;
use App\Enums\MilestoneStatus;
use App\Enums\DisputeStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreelanceConnectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed some basic category
        $this->category = JobCategory::create([
            'name' => 'Web Dev',
            'slug' => 'web-dev'
        ]);
    }

    public function test_user_registration_creates_profile_and_wallet(): void
    {
        $payload = [
            'name' => 'John Freelancer',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'freelancer',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'email', 'role']
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => 'freelancer',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->freelancerProfile);
        $this->assertNotNull($user->wallet);
        $this->assertEquals(0.00, $user->wallet->balance);
    }

    public function test_user_login_returns_token(): void
    {
        $user = User::create([
            'name' => 'Test Employer',
            'email' => 'employer@example.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::EMPLOYER,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'employer@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user',
                    'token'
                ]
            ]);
    }

    public function test_only_employers_can_post_jobs(): void
    {
        $freelancer = User::create([
            'name' => 'Free Lancer',
            'email' => 'free@example.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::FREELANCER,
            'email_verified_at' => now(),
        ]);

        $employer = User::create([
            'name' => 'Emp Loyer',
            'email' => 'emp@example.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::EMPLOYER,
            'email_verified_at' => now(),
        ]);

        $jobPayload = [
            'category_id' => $this->category->id,
            'title' => 'Build an API',
            'description' => 'Detailed description here',
            'budget' => 500.00,
            'type' => 'fixed',
        ];

        // Freelancer tries to post
        $response = $this->actingAs($freelancer)->postJson('/api/v1/jobs', $jobPayload);
        $response->assertStatus(403);

        // Employer posts
        $response = $this->actingAs($employer)->postJson('/api/v1/jobs', $jobPayload);
        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Build an API');
    }

    public function test_freelancers_can_submit_proposals_no_duplicates(): void
    {
        $employer = User::create([
            'name' => 'Employer',
            'email' => 'emp@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::EMPLOYER,
            'email_verified_at' => now(),
        ]);

        $freelancer = User::create([
            'name' => 'Freelancer',
            'email' => 'free@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::FREELANCER,
            'email_verified_at' => now(),
        ]);

        $job = Job::create([
            'employer_id' => $employer->id,
            'category_id' => $this->category->id,
            'title' => 'Laravel Integration',
            'description' => 'integrations details',
            'budget' => 1000.00,
            'type' => JobType::FIXED,
            'status' => JobStatus::OPEN,
        ]);

        $proposalPayload = [
            'cover_letter' => 'I can write clean PHP code.',
            'bid_amount' => 950.00,
            'estimated_duration_days' => 5,
        ];

        // Submit proposal
        $response = $this->actingAs($freelancer)->postJson("/api/v1/jobs/{$job->id}/proposals", $proposalPayload);
        $response->assertStatus(201);

        // Try duplicate
        $response2 = $this->actingAs($freelancer)->postJson("/api/v1/jobs/{$job->id}/proposals", $proposalPayload);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['job_id']);
    }

    public function test_hiring_and_escrow_workflow(): void
    {
        $employer = User::create([
            'name' => 'Employer',
            'email' => 'emp@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::EMPLOYER,
            'email_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $employer->id, 'balance' => 1500.00]);

        $freelancer = User::create([
            'name' => 'Freelancer',
            'email' => 'free@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::FREELANCER,
            'email_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $freelancer->id, 'balance' => 0.00]);

        $job = Job::create([
            'employer_id' => $employer->id,
            'category_id' => $this->category->id,
            'title' => 'Laravel API Development',
            'description' => 'scaffolding',
            'budget' => 1000.00,
            'type' => JobType::FIXED,
            'status' => JobStatus::OPEN,
        ]);

        $proposal = Proposal::create([
            'job_id' => $job->id,
            'freelancer_id' => $freelancer->id,
            'cover_letter' => 'Yes, let\'s construct this.',
            'bid_amount' => 1000.00,
            'estimated_duration_days' => 7,
            'status' => ProposalStatus::PENDING,
        ]);

        // Employer hires freelancer
        $response = $this->actingAs($employer)->postJson("/api/v1/proposals/{$proposal->id}/hire");
        $response->assertStatus(201);

        $contract = Contract::first();
        $this->assertNotNull($contract);
        $this->assertEquals(ContractStatus::ACTIVE, $contract->status);

        $milestone = Milestone::first();
        $this->assertNotNull($milestone);
        $this->assertEquals(MilestoneStatus::PENDING, $milestone->status);

        // Fund milestone
        $responseFund = $this->actingAs($employer)->postJson("/api/v1/contracts/{$contract->id}/milestones/{$milestone->id}/fund");
        $responseFund->assertStatus(200);

        // Verify wallets and escrows
        $employerWallet = Wallet::where('user_id', $employer->id)->first();
        $this->assertEquals(500.00, $employerWallet->balance); // 1500 - 1000

        $this->assertDatabaseHas('escrows', [
            'contract_id' => $contract->id,
            'milestone_id' => $milestone->id,
            'amount' => 1000.00,
            'status' => 'held',
        ]);

        // Freelancer submits work
        $responseSubmit = $this->actingAs($freelancer)->postJson("/api/v1/contracts/{$contract->id}/milestones/{$milestone->id}/submit");
        $responseSubmit->assertStatus(200);
        $this->assertEquals(MilestoneStatus::IN_REVIEW, $milestone->fresh()->status);

        // Employer releases funds
        $responseRelease = $this->actingAs($employer)->postJson("/api/v1/contracts/{$contract->id}/milestones/{$milestone->id}/release");
        $responseRelease->assertStatus(200);

        // Verify freelancer wallet balance
        $freelancerWallet = Wallet::where('user_id', $freelancer->id)->first();
        $this->assertEquals(1000.00, $freelancerWallet->balance);

        // Verify contract is completed
        $this->assertEquals(ContractStatus::COMPLETED, $contract->fresh()->status);
        $this->assertEquals(JobStatus::COMPLETED, $job->fresh()->status);
    }

    public function test_dispute_resolution_workflow(): void
    {
        $employer = User::create([
            'name' => 'Employer',
            'email' => 'emp@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::EMPLOYER,
            'email_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $employer->id, 'balance' => 1000.00]);

        $freelancer = User::create([
            'name' => 'Freelancer',
            'email' => 'free@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::FREELANCER,
            'email_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $freelancer->id, 'balance' => 0.00]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::ADMIN,
        ]);

        $job = Job::create([
            'employer_id' => $employer->id,
            'category_id' => $this->category->id,
            'title' => 'Project with Dispute',
            'description' => 'details',
            'budget' => 500.00,
            'type' => JobType::FIXED,
            'status' => JobStatus::OPEN,
        ]);

        $proposal = Proposal::create([
            'job_id' => $job->id,
            'freelancer_id' => $freelancer->id,
            'cover_letter' => 'bid bid',
            'bid_amount' => 500.00,
            'estimated_duration_days' => 5,
            'status' => ProposalStatus::ACCEPTED,
        ]);

        $contract = Contract::create([
            'job_id' => $job->id,
            'employer_id' => $employer->id,
            'freelancer_id' => $freelancer->id,
            'proposal_id' => $proposal->id,
            'title' => $job->title,
            'total_amount' => 500.00,
            'status' => ContractStatus::ACTIVE,
        ]);

        $milestone = Milestone::create([
            'contract_id' => $contract->id,
            'title' => 'Initial phase',
            'amount' => 500.00,
            'status' => MilestoneStatus::FUNDED,
        ]);

        // Setup escrow record manually
        $employerWallet = Wallet::where('user_id', $employer->id)->first();
        $employerWallet->decrement('balance', 500.00);

        \App\Models\Escrow::create([
            'contract_id' => $contract->id,
            'milestone_id' => $milestone->id,
            'amount' => 500.00,
            'status' => 'held',
        ]);

        // Freelancer raises dispute
        $responseDispute = $this->actingAs($freelancer)->postJson('/api/v1/disputes', [
            'contract_id' => $contract->id,
            'milestone_id' => $milestone->id,
            'reason' => 'Employer is not communicating.',
            'description' => 'I finished the work but they are ignoring my release requests.',
        ]);

        $responseDispute->assertStatus(201);
        $this->assertEquals(ContractStatus::DISPUTED, $contract->fresh()->status);

        $dispute = Dispute::first();
        $this->assertNotNull($dispute);

        // Admin resolves dispute: refunds the money back to the employer
        $responseResolve = $this->actingAs($admin)->postJson("/api/v1/disputes/{$dispute->id}/resolve", [
            'resolution' => 'refund',
            'details' => 'Refund processed due to lack of cooperation.',
        ]);

        $responseResolve->assertStatus(200);

        // Verify funds returned to employer wallet
        $this->assertEquals(1000.00, $employerWallet->fresh()->balance);

        // Verify contract is terminated
        $this->assertEquals(ContractStatus::TERMINATED, $contract->fresh()->status);
    }

    public function test_email_verification_workflow(): void
    {
        $freelancer = User::create([
            'name' => 'Unverified Freelancer',
            'email' => 'unverified@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::FREELANCER,
            'email_verified_at' => null,
        ]);

        $employer = User::create([
            'name' => 'Emp',
            'email' => 'emp@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::EMPLOYER,
            'email_verified_at' => now(),
        ]);
        $job = Job::create([
            'employer_id' => $employer->id,
            'category_id' => $this->category->id,
            'title' => 'Title',
            'description' => 'Desc',
            'budget' => 500.00,
            'type' => JobType::FIXED,
            'status' => JobStatus::OPEN,
        ]);

        $proposalPayload = [
            'cover_letter' => 'Test',
            'bid_amount' => 500,
            'estimated_duration_days' => 5,
        ];

        $responseBlocked = $this->actingAs($freelancer)->postJson("/api/v1/jobs/{$job->id}/proposals", $proposalPayload);
        $responseBlocked->assertStatus(403);

        $responseVerify = $this->actingAs($freelancer)->postJson("/api/v1/auth/verify-email");
        $responseVerify->assertStatus(200);
        $this->assertNotNull($freelancer->fresh()->email_verified_at);

        $responseSucceed = $this->actingAs($freelancer)->postJson("/api/v1/jobs/{$job->id}/proposals", $proposalPayload);
        $responseSucceed->assertStatus(201);
    }

    public function test_profile_update_workflow(): void
    {
        $freelancer = User::create([
            'name' => 'Old Name',
            'email' => 'free@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::FREELANCER,
            'email_verified_at' => now(),
        ]);

        $profilePayload = [
            'name' => 'New Name',
            'hourly_rate' => 55.50,
            'bio' => 'New developer bio details.',
            'portfolio_url' => 'https://myportfolio.com',
            'skills' => 'Laravel, Vue, AWS',
        ];

        $response = $this->actingAs($freelancer)->putJson('/api/v1/auth/profile', $profilePayload);
        $response->assertStatus(200);

        $this->assertEquals('New Name', $freelancer->fresh()->name);
        $this->assertEquals(55.50, $freelancer->fresh()->freelancerProfile->hourly_rate);
        $this->assertEquals('New developer bio details.', $freelancer->fresh()->freelancerProfile->bio);
        $this->assertEquals('https://myportfolio.com', $freelancer->fresh()->freelancerProfile->portfolio['url']);
        $this->assertCount(3, $freelancer->fresh()->freelancerProfile->skills);
    }

    public function test_close_job_early(): void
    {
        $employer = User::create([
            'name' => 'Employer',
            'email' => 'employer@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::EMPLOYER,
            'email_verified_at' => now(),
        ]);

        $job = Job::create([
            'employer_id' => $employer->id,
            'category_id' => $this->category->id,
            'title' => 'Title to Close',
            'description' => 'Desc',
            'budget' => 500.00,
            'type' => JobType::FIXED,
            'status' => JobStatus::OPEN,
        ]);

        $response = $this->actingAs($employer)->postJson("/api/v1/jobs/{$job->id}/close");
        $response->assertStatus(200);
        $this->assertEquals(JobStatus::CANCELLED, $job->fresh()->status);
    }
}
