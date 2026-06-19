<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\EmployerProfile;
use App\Models\FreelancerProfile;
use App\Models\AdminProfile;
use App\Models\Skill;
use App\Models\JobCategory;
use App\Models\Job;
use App\Models\Proposal;
use App\Models\Contract;
use App\Models\Milestone;
use App\Models\Wallet;
use App\Models\Escrow;
use App\Enums\UserRole;
use App\Enums\JobType;
use App\Enums\JobStatus;
use App\Enums\ProposalStatus;
use App\Enums\ContractStatus;
use App\Enums\MilestoneStatus;
use App\Enums\EscrowStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Categories
        $categories = [
            ['name' => 'Web Development', 'slug' => 'web-development'],
            ['name' => 'Mobile Development', 'slug' => 'mobile-development'],
            ['name' => 'UI/UX Design', 'slug' => 'ui-ux-design'],
            ['name' => 'Content Writing', 'slug' => 'content-writing'],
            ['name' => 'Digital Marketing', 'slug' => 'digital-marketing'],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[] = JobCategory::create($cat);
        }

        // 2. Seed Skills
        $skills = [
            ['name' => 'PHP', 'slug' => 'php'],
            ['name' => 'Laravel', 'slug' => 'laravel'],
            ['name' => 'React', 'slug' => 'react'],
            ['name' => 'Vue.js', 'slug' => 'vue-js'],
            ['name' => 'Swift', 'slug' => 'swift'],
            ['name' => 'Flutter', 'slug' => 'flutter'],
            ['name' => 'Figma', 'slug' => 'figma'],
            ['name' => 'SEO', 'slug' => 'seo'],
            ['name' => 'Copywriting', 'slug' => 'copywriting'],
            ['name' => 'Tailwind CSS', 'slug' => 'tailwind-css'],
        ];

        $skillModels = [];
        foreach ($skills as $sk) {
            $skillModels[] = Skill::create($sk);
        }

        // 3. Create Admin
        $admin = User::create([
            'name' => 'Platform Admin',
            'email' => 'admin@freelanceconnect.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'is_suspended' => false,
        ]);
        AdminProfile::create([
            'user_id' => $admin->id,
            'department' => 'Operations',
        ]);
        Wallet::create(['user_id' => $admin->id, 'balance' => 0.00]);

        // 4. Create Employers
        $employers = [];
        for ($i = 1; $i <= 3; $i++) {
            $employer = User::create([
                'name' => "Employer Company {$i}",
                'email' => "employer{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => UserRole::EMPLOYER,
                'is_suspended' => false,
            ]);

            EmployerProfile::create([
                'user_id' => $employer->id,
                'company_name' => "Company Acme {$i} Inc.",
                'bio' => "Acme {$i} is a leading tech service provider searching for rockstar freelancers.",
                'website' => "https://acme{$i}.example.com",
                'rating' => 0.00,
            ]);

            Wallet::create([
                'user_id' => $employer->id,
                'balance' => 5000.00, // Seed with some fake deposit funds for testing
            ]);

            $employers[] = $employer;
        }

        // 5. Create Freelancers
        $freelancers = [];
        for ($i = 1; $i <= 5; $i++) {
            $freelancer = User::create([
                'name' => "Freelancer Expert {$i}",
                'email' => "freelancer{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => UserRole::FREELANCER,
                'is_suspended' => false,
            ]);

            $profile = FreelancerProfile::create([
                'user_id' => $freelancer->id,
                'title' => "Senior Developer / Designer {$i}",
                'bio' => "Hi! I am a software professional with 5+ years of experience specializing in custom business solutions.",
                'hourly_rate' => 30.00 + ($i * 5),
                'rating' => 0.00,
            ]);

            Wallet::create([
                'user_id' => $freelancer->id,
                'balance' => 0.00,
            ]);

            // Sync random skills (2-4 skills)
            $randomSkills = collect($skillModels)->random(rand(2, 4))->pluck('id')->toArray();
            $profile->skills()->sync($randomSkills);

            $freelancers[] = $freelancer;
        }

        // 6. Create Jobs
        $jobs = [];
        $jobTitles = [
            'Build a Custom CRM System in Laravel',
            'Figma UI/UX Design for Mobile E-Commerce App',
            'SEO Audit and Content Optimization Strategy',
            'Cross-platform Flutter App for Delivery Service',
            'Graphic Redesign for Corporate landing page'
        ];

        for ($i = 0; $i < 5; $i++) {
            $employer = $employers[$i % count($employers)];
            $category = $categoryModels[$i % count($categoryModels)];

            $job = Job::create([
                'employer_id' => $employer->id,
                'category_id' => $category->id,
                'title' => $jobTitles[$i],
                'description' => "We are looking for an experienced freelancer to help us complete the project: {$jobTitles[$i]}. Please provide cover letter, estimated timeframe, and portfolio links.",
                'budget' => 500.00 + ($i * 250),
                'type' => ($i % 2 === 0) ? JobType::FIXED : JobType::HOURLY,
                'status' => JobStatus::OPEN,
            ]);

            // Sync random skills to job
            $randomSkills = collect($skillModels)->random(rand(2, 3))->pluck('id')->toArray();
            $job->skills()->sync($randomSkills);

            $jobs[] = $job;
        }

        // 7. Create Proposals
        $proposals = [];
        foreach ($jobs as $idx => $job) {
            // Let some freelancers submit proposals
            $biddingFreelancers = collect($freelancers)->random(2);
            foreach ($biddingFreelancers as $f) {
                $proposals[] = Proposal::create([
                    'job_id' => $job->id,
                    'freelancer_id' => $f->id,
                    'cover_letter' => "Dear Hiring Manager, I am very interested in this opportunity. I have deep experience in this area and can deliver clean results on time. Check my portfolio.",
                    'bid_amount' => $job->budget - 50.00,
                    'estimated_duration_days' => 10,
                    'status' => ProposalStatus::PENDING,
                ]);
            }
        }

        // 8. Setup one complete workflow contract (Employer 1 hires Freelancer 1 for Job 1)
        $emp1 = $employers[0];
        $free1 = $freelancers[0];
        $job1 = $jobs[0];

        $proposal = Proposal::create([
            'job_id' => $job1->id,
            'freelancer_id' => $free1->id,
            'cover_letter' => "I want to work on this Laravel CRM system!",
            'bid_amount' => 1000.00,
            'estimated_duration_days' => 14,
            'status' => ProposalStatus::ACCEPTED,
        ]);

        // Mark other proposals for Job 1 as rejected
        Proposal::where('job_id', $job1->id)->where('id', '!=', $proposal->id)->update(['status' => ProposalStatus::REJECTED]);

        // Update Job 1 to in_progress
        $job1->update(['status' => JobStatus::IN_PROGRESS]);

        // Create Contract
        $contract = Contract::create([
            'job_id' => $job1->id,
            'employer_id' => $emp1->id,
            'freelancer_id' => $free1->id,
            'proposal_id' => $proposal->id,
            'title' => $job1->title,
            'total_amount' => 1000.00,
            'status' => ContractStatus::ACTIVE,
            'start_date' => now(),
        ]);

        // Milestone 1 (Funded/Held in escrow)
        $m1 = Milestone::create([
            'contract_id' => $contract->id,
            'title' => 'Phase 1: DB design & API Scaffolding',
            'amount' => 500.00,
            'status' => MilestoneStatus::FUNDED,
            'due_date' => now()->addDays(7),
        ]);

        // Deduct from Employer 1 wallet balance and record escrow hold
        $empWallet = Wallet::where('user_id', $emp1->id)->first();
        $empWallet->decrement('balance', 500.00);

        \App\Models\Transaction::create([
            'wallet_id' => $empWallet->id,
            'amount' => -500.00,
            'type' => TransactionType::ESCROW_HOLD,
            'description' => "Escrow hold for Contract milestone: {$m1->title}",
            'reference_type' => Milestone::class,
            'reference_id' => $m1->id,
        ]);

        Escrow::create([
            'contract_id' => $contract->id,
            'milestone_id' => $m1->id,
            'amount' => 500.00,
            'status' => EscrowStatus::HELD,
        ]);

        // Milestone 2 (Pending - unfunded)
        $m2 = Milestone::create([
            'contract_id' => $contract->id,
            'title' => 'Phase 2: UI implementation & final integrations',
            'amount' => 500.00,
            'status' => MilestoneStatus::PENDING,
            'due_date' => now()->addDays(14),
        ]);
    }
}
