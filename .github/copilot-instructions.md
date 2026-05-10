# SapinSolidaire - Project Context for Copilot

## Project Overview

SapinSolidaire is a Laravel 12 Livewire application for managing Christmas gift donations to families in need. It's a starter kit from Laravel's official Livewire template. The application allows families to request help, validators to verify eligibility, organizers to manage the overall process, and reception staff to track gift distribution.

## Tech Stack

- **PHP 8.2+**
- **Laravel 12** - Framework
- **Livewire 4** - Full-stack framework (no traditional Blade components)
- **Flux UI** - Laravel's modern UI component library
- **Vite** - Asset bundling
- **PostgreSQL/SQLite** - Database (UUIDs primary keys throughout)
- **Pest PHP** - Testing framework
- **Laravel Pint** - Code styling
- **barryvdh/laravel-dompdf** - PDF generation
- **giggsey/libphonenumber-for-php** - Phone number validation
- **Laravel Fortify** - Authentication + 2FA support

## Architecture Patterns

### Directory Structure
```
app/
  Actions/          - Reusable action classes (e.g., Logout)
  Concerns/         - Reusable traits (e.g., WithSearch)
  Console/          - Artisan commands
  Http/
    Controllers/    - Minimal controllers (only Controller base)
    Middleware/     - Custom middleware (CheckRole, CheckAnyRole, SetLocale)
  Livewire/
    Admin/          - Admin panel components
    Family/         - Public-facing family components
  Mail/             - Mailable classes
  Models/           - Eloquent models (all use UUIDs)
  Services/         - Business logic services
bootstrap/
  app.php           - Application bootstrap
  providers.php     - Service provider registration
config/             - Configuration files
database/
  migrations/       - Database migrations (all use UUID primary keys)
  factories/        - Model factories for testing
  seeders/          - Database seeders
resources/
  views/
    livewire/       - Livewire component Blade templates
    components/     - Blade components
    emails/         - Email templates
    layouts/        - Main layout files
    pdf/            - PDF template layouts
    partials/       - Reusable partial views
routes/
  web.php           - Web routes
  settings.php      - Settings-specific routes
  console.php       - Artisan commands
storage/views/      - Compiled Blade templates
tests/              - Pest PHP test suite
```

### Key Conventions

1. **UUIDs Everywhere**: All models use `HasUuids` trait. Primary keys and foreign keys are `uuid`. Never assume `id` columns exist.

2. **Role-Based Access Control**: Custom role system (not Spatie Permission). Roles are constants on the `Role` model:
   - `Role::ADMIN` ('admin')
   - `Role::VALIDATOR` ('validator')
   - `Role::FAMILY_VALIDATOR` ('validateFamily')
   - `Role::ORGANIZER` ('organizer')
   - `Role::RECEPTION` ('reception')
   - `Role::VISITOR` ('visitor')

3. **Middleware**: Use `CheckAnyRole` and `CheckRole` middleware for route protection:
   ```php
   ->middleware('any.role:'.Role::FAMILY_VALIDATOR.','.Role::VALIDATOR.','.Role::ADMIN)
   ->middleware('role:'.Role::ADMIN)
   ```

4. **Livewire Components**: All UI is built with Livewire 4 class-based components. Each component has a corresponding Blade view in `resources/views/livewire/`.

5. **Status Constants**: Models define status constants as public static properties:
   ```php
   public const STATUS_PENDING = 'pending';
   public const STATUS_VALIDATED = 'validated';
   ```

6. **Season-based Data**: Most business logic revolves around `Seasons`. Always consider season context for queries.

7. **Bilingual Support**: The app supports French (fr) and English (en). All user-facing text must be translatable via `__()` helper. French is the default/primary language.

8. **Admin Panel**: All admin routes are under `/admin/*` prefix with role-based middleware.

## Model Reference

### Core Models
- **User** - Auth user with roles (many-to-many), 2FA support, UUIDs
- **Role** - Role definitions with constants, many-to-many with User
- **Family** - Household/family unit
- **Child** - Child associated with a family and season (gift recipient)
- **GiftRequest** - Family's request for a specific season (has status workflow)
- **Season** - A donation period (e.g., Christmas 2025)
- **EmailToken** - Token-based email system
- **Setting** - Application settings (key-value store)
- **PickupSlot** - Gift pickup time slots
- **GeneratedPdf** - Generated PDF document tracking

### GiftRequest Status Workflow
`pending` → `validated` → `rejected_final`
                        ↓
                    `rejected` (can be re-submitted)

### Child Status Workflow
`pending` → `validated` → `printed` → `received` → `given`

## Database Patterns

- All migrations use `uuid` primary keys
- Foreign keys use `foreignUuid()->constrained()->cascadeOnDelete()`
- Unique constraints for family/season combinations
- Index on status fields for performance
- Soft deletes not used by default
- Timestamps enabled on all tables

## Testing Conventions

- Use **Pest PHP** for all tests
- Tests go in `tests/` directory
- Feature tests for HTTP/Livewire interactions
- Use `pestphp/pest-plugin-laravel` helpers
- Model factories in `database/factories/`
- Run tests: `composer test` or `composer test:lint`
- Code styling: `composer lint` (runs Pint)

## Routing Patterns

```php
// Public routes
Route::get('/', Home::class)->name('home');
Route::get('/cadeau/{token}', GiftRequestForm::class)->name('gift.form');

// Admin routes with role middleware
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', Dashboard::class)
        ->middleware('any.role:'.Role::ADMIN.','.Role::VALIDATOR)
        ->name('admin.dashboard');
    
    // Admin-only routes
    Route::middleware('role:'.Role::ADMIN)->group(function () {
        Route::get('/settings', SettingsManagement::class)->name('admin.settings');
    });
});
```

## Livewire Patterns

### Component Structure
```php
<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class Dashboard extends Component
{
    // Public properties for wire:model binding
    public ?Season $activeSeason = null;
    public int $totalFamilies = 0;

    // Mount is called on initial load
    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
    }

    // Actions triggered by user interactions
    public function save(): void
    {
        // Business logic
    }

    // Render method returns view path
    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
```

### View Patterns
- Views in `resources/views/livewire/{namespace}/{ComponentName}.blade.php`
- Use Flux UI components for consistent styling
- Use `wire:loading`, `wire:target` for loading states
- Use `wire:confirm` for destructive actions

## Email System

- Mailable classes in `app/Mail/`
- Emails queued via Laravel queue system
- Email tokens used for authentication-free access (family gift requests)
- Template views in `resources/views/emails/`

## PDF Generation

- Uses `barryvdh/laravel-dompdf`
- PDF templates in `resources/views/pdf/`
- Generated PDFs stored in `Storage::disk('local')` with tracking in `generated_pdfs` table
- Admin route for downloading: `/admin/cartes/telecharg/{generatedPdf}`

## Service Configuration

### Queue
- Email and heavy operations use Laravel queue
- Run with: `php artisan queue:work`

### Storage
- Files stored via Laravel filesystem
- Local disk for proofs, documents
- Access via `Storage::disk('local')`

## Coding Standards

- Follow Laravel Pint PSR-12 style (see `pint.json`)
- Type hint everything (return types + parameter types)
- Use PHPDoc blocks for models and components
- Keep Livewire components thin - push complex logic to Services
- Use constants for status values and magic strings
- French translations in `lang/fr/`, English in `lang/en/`

## Important Notes

1. **Never assume auto-increment IDs** - All IDs are UUIDs
2. **Always check active season** when filtering data
3. **Use role constants** (`Role::ADMIN`) not raw strings
4. **All routes have names** - Always use `route()` and `->name()` helpers
5. **Season-based queries** should always consider which season
6. **French-first** - Default language is French, plan for i18n
7. **Livewire 4** - Uses class-based components, not the old Blade component approach
8. **Two-factor authentication** is enabled via Fortify
9. **Family validation workflow** involves multiple roles and status transitions

## CSS Styling Guidelines

### CSS File Location
- **Main CSS file**: `resources/css/app.css`
- All custom styles are defined in the `@layer components` section of this file
- Built via Vite and imported in your Blade layouts via `@vite(['resources/css/app.css', 'resources/js/app.js'])`

### Reusable Component Classes
Always use these predefined classes instead of writing inline Tailwind utilities. They unify the look of the application.

#### Cards
| Class | Usage | Style |
|-------|-------|-------|
| `.card` | Container/wrapper for any content block | `bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-8` |

#### Typography
| Class | Usage | Style |
|-------|-------|-------|
| `.section-title` | Section headings with bottom border | `text-lg font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-zinc-600 pb-2` |

#### Form Elements
| Class | Usage | Style |
|-------|-------|-------|
| `.field-label` | Label for any form field | `block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1` |
| `.field-input` | Text input, select, textarea (normal state) | `w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white` |
| `.field-input-error` | Input with validation error state | Same as `.field-input` but with `border-red-500` |
| `.field-error` | Error message text below field | `mt-1 text-sm text-red-600 dark:text-red-400` |

#### Buttons
| Class | Usage | Style |
|-------|-------|-------|
| `.btn-primary` | Main action button (full width) | `bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg` |
| `.btn-primary:disabled` | Disabled state for primary button | `opacity-50 cursor-not-allowed` |
| `.btn-secondary` | Secondary/outline button | `border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700` |
| `.btn-confirm` | Small inline confirm/accept button | `bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg` |

#### Badges & Banners
| Class | Usage | Style |
|-------|-------|-------|
| `.notice-success` | Success/accepted condition display |
| `.notice-info` | Info/notice banner |
| `.notice-error` | Error/issue banner |
| `.notice-warning` | Warning banner |

#### Layout Helpers
| Class | Usage | Style |
|-------|-------|-------|
| `.agenda-item` | Timeline/list item with icon | `flex items-start gap-3` |
| `.slot-pill` | Pickup slot badge/pill | `flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-zinc-700 rounded-lg px-3 py-2` |

### Styling Rules

1. **Always reuse classes**: If a class already exists (e.g., `.card`), use it instead of re-typing the Tailwind utilities.

2. **Dark mode**: All classes include `dark:` variants. Always test dark mode compatibility.

3. **Adding new classes**: When a new reusable style is needed:
   - Add it to the `@layer components` section in `resources/css/app.css`
   - Follow the naming convention: `.element-name` (kebab-case with dot prefix)
   - Group by type: cards, typography, form, buttons, badges, layout
   - Include `dark:` variant if colors are affected
   - Document the class in this file

4. **Color palette**: 
   - Primary/accent color: Green (`green-600` / `green-700`)
   - Backgrounds: White (light) / Zinc-800 (dark)
   - Borders: Gray-300 (light) / Zinc-600 (dark)
   - Text: Gray-700/800 (light) / White (dark)
   - Errors: Red-500/600
   - Info: Blue-50/200 / Blue-900/20

5. **Flux UI components**: Use Flux UI components (`<flux.*>`) for standard UI elements (inputs, buttons, modals, etc.). Only use custom CSS classes for application-specific styling.

6. **Example usage in Blade**:
   ```blade
   <div class="card">
       <h2 class="section-title">Section Title</h2>
       
       <label class="field-label">{{ __('First Name') }}</label>
       <input class="field-input" wire:model="firstName" />
       
       @error('firstName') <span class="field-error">{{ $message }}</span> @enderror
       
       <button class="btn-primary mt-4">
           <span>Save</span>
       </button>
   </div>
   ```

## Common Tasks

### Adding a new admin page
1. Create Livewire component in `app/Livewire/Admin/`
2. Create view in `resources/views/livewire/admin/`
3. Add route in `routes/web.php` with appropriate role middleware
4. Add navigation link in sidebar layout

### Adding a new model
1. Create model with `php artisan make:model Name`
2. Use `HasUuids` trait
3. Define status constants as public const
4. Create migration with UUID primary key
5. Create factory for testing
6. Add to appropriate seed if needed

### Adding a new role
1. Add constant to `Role` model
2. Update `CheckRole` middleware if needed
3. Add middleware to routes
4. Add user assignment UI in user management
