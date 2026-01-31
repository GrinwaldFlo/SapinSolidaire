<?php

namespace App\Providers;

use App\Models\Role;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureGates(): void
    {
        Gate::define('admin', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('validate', function ($user) {
            return $user->isValidator();
        });

        Gate::define('organize', function ($user) {
            return $user->isOrganizer();
        });

        Gate::define('reception', function ($user) {
            return $user->isReception();
        });

        Gate::define('access-admin', function ($user) {
            return $user->hasAnyRole([
                Role::ADMIN,
                Role::VALIDATOR,
                Role::ORGANIZER,
                Role::RECEPTION,
            ]);
        });
    }
}
