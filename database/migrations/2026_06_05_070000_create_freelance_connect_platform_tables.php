<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Profiles
        Schema::create('employer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('company_name')->nullable();
            $table->text('bio')->nullable();
            $table->string('website')->nullable();
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('freelancer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->decimal('hourly_rate', 10, 2)->default(0.00);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->json('portfolio')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('department')->nullable();
            $table->timestamps();
        });

        // 2. Skills
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('freelancer_skills', function (Blueprint $table) {
            $table->foreignId('freelancer_profile_id')->constrained('freelancer_profiles')->onDelete('cascade');
            $table->foreignId('skill_id')->constrained('skills')->onDelete('cascade');
            $table->primary(['freelancer_profile_id', 'skill_id']);
        });

        // 3. Job Categories
        Schema::create('job_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // 4. Jobs
        Schema::create('posted_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('job_categories')->onDelete('restrict');
            $table->string('title');
            $table->text('description');
            $table->decimal('budget', 10, 2);
            $table->string('type'); // fixed, hourly
            $table->string('status'); // draft, open, in_progress, completed, cancelled
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('type');
        });

        Schema::create('job_skills', function (Blueprint $table) {
            $table->foreignId('job_id')->constrained('posted_jobs')->onDelete('cascade');
            $table->foreignId('skill_id')->constrained('skills')->onDelete('cascade');
            $table->primary(['job_id', 'skill_id']);
        });

        Schema::create('job_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('posted_jobs')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->integer('file_size');
            $table->timestamps();
        });

        // 5. Proposals
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('posted_jobs')->onDelete('cascade');
            $table->foreignId('freelancer_id')->constrained('users')->onDelete('cascade');
            $table->text('cover_letter');
            $table->decimal('bid_amount', 10, 2);
            $table->integer('estimated_duration_days');
            $table->string('status'); // pending, accepted, rejected, withdrawn
            $table->timestamps();

            $table->unique(['job_id', 'freelancer_id']); // prevent duplicate proposals
            $table->index('status');
        });

        // 6. Contracts
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('posted_jobs')->onDelete('restrict');
            $table->foreignId('employer_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('freelancer_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('proposal_id')->constrained('proposals')->onDelete('restrict');
            $table->string('title');
            $table->decimal('total_amount', 10, 2);
            $table->string('status'); // active, completed, disputed, terminated
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->string('status'); // pending, funded, in_review, released, refunded
            $table->timestamp('due_date')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // 7. Wallets & Transactions & Escrow
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('type'); // deposit, withdraw, escrow_hold, escrow_release, refund, transfer
            $table->string('description');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('escrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->foreignId('milestone_id')->nullable()->constrained('milestones')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('status'); // held, released, refunded
            $table->timestamps();

            $table->index('status');
        });

        // 8. Conversations & Messages
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('conversation_user', function (Blueprint $table) {
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->primary(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('message_text');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        // 9. Reviews
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewee_id')->constrained('users')->onDelete('cascade');
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'reviewer_id']);
        });

        // 10. Disputes
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->foreignId('milestone_id')->nullable()->constrained('milestones')->onDelete('cascade');
            $table->foreignId('raiser_id')->constrained('users')->onDelete('cascade');
            $table->string('reason');
            $table->text('description');
            $table->string('evidence_path')->nullable();
            $table->string('status'); // pending, resolving, resolved, rejected
            $table->text('resolution_details')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index('status');
        });

        // 11. Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_user');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('escrows');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('milestones');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('job_attachments');
        Schema::dropIfExists('job_skills');
        Schema::dropIfExists('posted_jobs');
        Schema::dropIfExists('job_categories');
        Schema::dropIfExists('freelancer_skills');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('admin_profiles');
        Schema::dropIfExists('freelancer_profiles');
        Schema::dropIfExists('employer_profiles');
    }
};
