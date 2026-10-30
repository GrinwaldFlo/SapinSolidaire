<?php

namespace App\Providers;

use App\Http\Middleware\CheckAnyRole;
use App\Http\Middleware\CheckRole;
use App\Models\Role;
use Carbon\CarbonImmutable;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

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
        $this->configureBladeDirectives();
        $this->configureLivewire();
        $this->configureTrustedProxies();
    }

    protected function configureLivewire(): void
    {
        // Re-check roles on every Livewire action, not only when the page is first loaded.
        Livewire::addPersistentMiddleware([
            CheckRole::class,
            CheckAnyRole::class,
        ]);
    }

    protected function configureTrustedProxies(): void
    {
        // Behind a reverse proxy, trust it so request()->ip() is the client IP (used by IP rate limits).
        $proxies = config('app.trusted_proxies');

        if ($proxies) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
    }

    protected function configureBladeDirectives(): void
    {
        // Escaped multiline text with URLs as links and line breaks as <br>, e.g. custom texts in HTML emails.
        Blade::directive('multiline', fn (string $expression): string => "<?php echo \\App\\Services\\TextLinkifier::toMultilineHtml({$expression}); ?>");

        // Escaped free text with URLs as links, e.g. custom texts on pages (keep line breaks with CSS).
        Blade::directive('linkify', fn (string $expression): string => "<?php echo \\App\\Services\\TextLinkifier::toHtml({$expression}, 'link'); ?>");

        // Body class turning the whole background pink on the test website (APP_ENV=test).
        Blade::directive('envBodyClass', fn (): string => "<?php echo app()->environment('test') ? 'env-test' : ''; ?>");
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

        Gate::define('validateFamily', function ($user) {
            return $user->isFamilyValidator();
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
                Role::FAMILY_VALIDATOR,
                Role::ORGANIZER,
                Role::RECEPTION,
            ]);
        });
    }
}
