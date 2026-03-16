<?php

namespace App\Providers;

use App\Models\Chat;
use App\Policies\ChatPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Chat::class => ChatPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('manage-configurations', function ($user) {

            if ($user->role === 'admin' && $user->school_id) {
                return true;
            }

            if ($user->role === 'teacher' && !$user->school_id) {
                return true;
            }

            return false;
        });
    }
}
