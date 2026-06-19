<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            \App\Repositories\Eloquent\UserRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\JobRepositoryInterface::class,
            \App\Repositories\Eloquent\JobRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\ProposalRepositoryInterface::class,
            \App\Repositories\Eloquent\ProposalRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\ContractRepositoryInterface::class,
            \App\Repositories\Eloquent\ContractRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\WalletRepositoryInterface::class,
            \App\Repositories\Eloquent\WalletRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\MessageRepositoryInterface::class,
            \App\Repositories\Eloquent\MessageRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\DisputeRepositoryInterface::class,
            \App\Repositories\Eloquent\DisputeRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Conversation::class, \App\Policies\MessagePolicy::class);
    }
}
