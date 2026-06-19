<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\JobController;
use App\Http\Controllers\API\V1\ProposalController;
use App\Http\Controllers\API\V1\ContractController;
use App\Http\Controllers\API\V1\WalletController;
use App\Http\Controllers\API\V1\MessageController;
use App\Http\Controllers\API\V1\ReviewController;
use App\Http\Controllers\API\V1\DisputeController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\SkillController;
use App\Http\Controllers\API\V1\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{id}', [JobController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/skills', [SkillController::class, 'index']);

    // Authenticated routes
    Route::middleware(['auth:sanctum', 'last-seen'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

        // Wallet
        Route::get('/wallet', [WalletController::class, 'show']);
        Route::post('/wallet/deposit', [WalletController::class, 'deposit']);

        // Job CRUD
        Route::post('/jobs', [JobController::class, 'store'])->middleware('role:employer');
        Route::put('/jobs/{id}', [JobController::class, 'update'])->middleware('role:employer');
        Route::delete('/jobs/{id}', [JobController::class, 'destroy'])->middleware('role:employer');
        Route::post('/jobs/{id}/close', [JobController::class, 'close'])->middleware('role:employer');

        // Proposals
        Route::get('/proposals', [ProposalController::class, 'index']);
        Route::get('/jobs/{jobId}/proposals', [ProposalController::class, 'getJobProposals']);
        Route::post('/jobs/{jobId}/proposals', [ProposalController::class, 'store'])->middleware('role:freelancer');
        Route::post('/proposals/{id}/withdraw', [ProposalController::class, 'withdraw'])->middleware('role:freelancer');
        Route::post('/proposals/{id}/reject', [ProposalController::class, 'reject'])->middleware('role:employer');

        // Contracts & Hiring
        Route::post('/proposals/{proposalId}/hire', [ContractController::class, 'hire'])->middleware('role:employer');
        Route::get('/contracts', [ContractController::class, 'index']);
        Route::get('/contracts/{id}', [ContractController::class, 'show']);
        Route::post('/contracts/{contractId}/milestones', [ContractController::class, 'addMilestone'])->middleware('role:employer');
        Route::post('/contracts/{contractId}/milestones/{milestoneId}/fund', [ContractController::class, 'fundMilestone'])->middleware('role:employer');
        Route::post('/contracts/{contractId}/milestones/{milestoneId}/submit', [ContractController::class, 'submitWork'])->middleware('role:freelancer');
        Route::post('/contracts/{contractId}/milestones/{milestoneId}/release', [ContractController::class, 'releaseMilestone'])->middleware('role:employer');
        Route::post('/contracts/{id}/end', [ContractController::class, 'end']);

        // Reviews
        Route::post('/contracts/{contractId}/reviews', [ReviewController::class, 'store']);

        // Disputes
        Route::get('/disputes', [DisputeController::class, 'index']);
        Route::get('/disputes/{id}', [DisputeController::class, 'show']);
        Route::post('/disputes', [DisputeController::class, 'store']);
        Route::post('/disputes/{id}/resolve', [DisputeController::class, 'resolve'])->middleware('role:admin');
        
        // Admin routes
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('/stats', [\App\Http\Controllers\API\V1\AdminController::class, 'stats']);
            Route::get('/users', [\App\Http\Controllers\API\V1\AdminController::class, 'users']);
            Route::get('/jobs', [\App\Http\Controllers\API\V1\AdminController::class, 'jobs']);
            Route::post('/users/{id}/toggle-suspend', [\App\Http\Controllers\API\V1\AdminController::class, 'suspendUser']);
        });

        // Role-specific stats
        Route::get('/employer/stats', [\App\Http\Controllers\API\V1\StatsController::class, 'employerStats'])->middleware('role:employer');
        Route::get('/freelancer/stats', [\App\Http\Controllers\API\V1\StatsController::class, 'freelancerStats'])->middleware('role:freelancer');

        // Chat / Messages
        Route::get('/conversations', [MessageController::class, 'index']);
        Route::post('/conversations', [MessageController::class, 'storeConversation']);
        Route::get('/conversations/{id}/messages', [MessageController::class, 'show']);
        Route::post('/conversations/{id}/messages', [MessageController::class, 'storeMessage']);
        Route::post('/conversations/{id}/read', [MessageController::class, 'markAsRead']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

        // Broadcasting auth
        Route::match(['get', 'post'], '/broadcasting/auth', '\\Illuminate\\Broadcasting\\BroadcastController@authenticate')
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
    });
});
