# SapinSolidaire - Project Context for Copilot

## Project Overview

SapinSolidaire is a Laravel 12 Livewire application for managing Christmas gift donations to families in need. The application serves families requesting help, validators verifying eligibility, organizers managing the process, and reception staff tracking gift distribution.

## Tech Stack

- **PHP 8.2+** / **Laravel 12**
- **Livewire 4** - full-stack UI (class-based components only)
- **Flux UI** - component library (`<flux.*>` tags)
- **Tailwind CSS v4** - utility layer (via `app.css`)
- **Vite** - asset bundling
- **PostgreSQL/SQLite** - UUIDs as primary keys throughout
- **Pest PHP** - testing framework
- **Laravel Pint** - PSR-12 code styling
- **barryvdh/laravel-dompdf** - PDF generation
- **giggsey/libphonenumber-for-php** - phone validation
- **Laravel Fortify** - authentication + 2FA

## Directory Structure

```
app/
  Actions/        - Reusable action classes
  Concerns/       - Shared traits (e.g. WithSearch)
  Http/Middleware/ - CheckRole, CheckAnyRole, SetLocale
  Livewire/Admin/ - Admin panel Livewire components
  Livewire/Family/- Public-facing Livewire components
  Mail/           - Mailable classes
  Models/         - Eloquent models (all use HasUuids)
  Services/       - Business logic services
resources/views/
  livewire/       - Blade views for Livewire components
  components/     - Reusable Blade components
  emails/         - Email templates
  layouts/        - app / auth / family layouts
  pdf/            - DomPDF templates
  partials/       - Shared partials
routes/
  web.php         - All routes (admin under /admin/* prefix)
tests/            - Pest PHP test suite
```

## Key Conventions

1. **UUIDs everywhere** - `HasUuids` on all models; never assume integer IDs.
2. **Role constants** - use `Role::ADMIN`, `Role::VALIDATOR`, etc., never raw strings.
3. **Middleware** - `role:` and `any.role:` for route protection.
4. **Livewire 4** - class-based components; one Blade view per component under `resources/views/livewire/`.
5. **Status constants** - `public const STATUS_PENDING = 'pending'` pattern on models.
6. **Season-scoped queries** - always filter by active season.
7. **French-first i18n** - all user-facing strings via `__()`. French in `lang/fr/`, English in `lang/en/`.
8. **Named routes** - always use `route('name')` and `->name()`.
9. **Thin components** - push business logic to `Services/`.

## Model Reference

| Model | Purpose |
|---|---|
| `User` | Auth user, many-to-many roles, 2FA |
| `Role` | Role definitions with constants |
| `Family` | Household unit |
| `Child` | Gift recipient, linked to family + season |
| `GiftRequest` | Family request for a season (status workflow) |
| `Season` | Donation period (e.g. Christmas 2025) |
| `EmailToken` | Token-based auth for families |
| `Setting` | Key-value app settings |
| `PickupSlot` | Gift pickup time slots |
| `GeneratedPdf` | PDF generation tracking |

**GiftRequest workflow:** `pending` -> `validated` -> `rejected_final` / `rejected`

**Child workflow:** `pending` -> `validated` -> `printed` -> `received` -> `given`

## Database Patterns

- UUID primary keys on all tables
- `foreignUuid()->constrained()->cascadeOnDelete()`
- No soft deletes by default; timestamps always enabled

## Testing

- **Pest PHP** - `composer test` / `composer test:lint`
- **Pint** - `composer lint`
- Feature tests for HTTP and Livewire interactions
- Factories in `database/factories/`

## Routing Pattern

```php
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', Dashboard::class)
        ->middleware('any.role:'.Role::ADMIN.','.Role::VALIDATOR)
        ->name('admin.dashboard');

    Route::middleware('role:'.Role::ADMIN)->group(function () {
        Route::get('/settings', SettingsManagement::class)->name('admin.settings');
    });
});
```

---

## CSS Styling - Complete Reference

> **Rule**: Never write raw Tailwind color, size, or spacing utilities directly in Blade templates.
> Always use the shared classes below. If no class fits, add one to `resources/css/app.css` under `@layer components` and document it here.

The live visual reference for all classes is: `/admin/css-showcase`

### Cards

| Class | Usage |
|---|---|
| `.card` | Main content block - large padding, shadow |
| `.card-sm` | Compact content block - reduced padding |
| `.stat-card` | Dashboard stat tile |
| `.card-footer` | Centered footer/hint below a card |

### Typography

| Class | Usage |
|---|---|
| `.section-title` | Section heading with bottom border |
| `.sub-label` | Uppercase spaced annotation label |
| `.field-label` | Form field label |
| `.field-error` | Validation error message below a field |
| `.text-muted` | Secondary / descriptive text |
| `.detail-label` | Key in a key-value pair (e.g. "Prenom :") |
| `.detail-value` | Value in a key-value pair |
| `.link` | Inline hyperlink |

### Stat Labels (dashboard numbers)

| Class | Usage |
|---|---|
| `.label-title` | Small caption above a stat number |
| `.label-value` | Large neutral stat number |
| `.label-value--warning` | Orange stat number |
| `.label-value--success` | Green stat number |
| `.label-value--info` | Blue stat number |
| `.label-value--yellow` | Yellow stat number |
| `.label-value--purple` | Purple stat number |

### Form Fields

| Class | Usage |
|---|---|
| `.field-input` | Text input, select, textarea - normal state |
| `.field-input-error` | Same but with red border for validation errors |

### Buttons

| Class | Color | Usage |
|---|---|---|
| `.btn-primary` | Green (full width) | Primary submit / save action |
| `.btn-confirm` | Green (compact) | Inline confirm / accept |
| `.btn-secondary` | Outline gray | Cancel / back |
| `.btn-blue` | Blue | Info / generate / send actions |
| `.btn-warning` | Yellow | Ask for correction |
| `.btn-danger` | Red | Delete / reject |
| `.btn-gray` | Gray | Neutral secondary action |

> `.btn-primary:disabled` applies `opacity-50 cursor-not-allowed` automatically.

### Badges (status pills)

| Class | Color | Status |
|---|---|---|
| `.badge--pending` | Yellow | A valider |
| `.badge--validated` | Green | Valide |
| `.badge--rejected` | Red | Rejete |
| `.badge--printed` | Purple | Imprime |
| `.badge--received` | Cyan | Recu |
| `.badge--given` | Green | Remis |
| `.badge--info` | Blue | Info / role |
| `.badge--neutral` | Gray | Neutral state |
| `.badge--warning` | Orange | Warning state |

### Notices / Banners

| Class | Usage |
|---|---|
| `.notice-info` | General information banner |
| `.notice-success` | Success / confirmation banner |
| `.notice-warning` | Warning / caution banner |
| `.notice-error` | Error / problem banner |

### Tables

| Class | Usage |
|---|---|
| `.table-container` | Wraps the full `<table>` - white card, rounded, shadow |
| `.table-header` | `<th>` - uppercase, muted, small |
| `.table-divider` | `<tbody>` - adds row dividers |
| `.table-cell` | `<td>` - primary text color |
| `.table-cell-muted` | `<td>` - muted text color |
| `.table-empty` | `<td colspan>` - centered empty state message |

### Modals

| Class | Usage |
|---|---|
| `.modal-backdrop` | Fixed full-screen dark overlay |
| `.modal-panel` | White/dark box, rounded, scrollable |
| `.modal-header` | Top bar with title and close button |
| `.modal-footer` | Bottom bar with action buttons |

### Layout Helpers

| Class | Usage |
|---|---|
| `.agenda-item` | Timeline row with icon + text |
| `.slot-pill` | Pickup time slot badge |

### Styling Rules

1. **No raw utilities for color/size/spacing in Blade** - always use a shared class.
2. **Dark mode included** - all shared classes have `dark:` variants; do not add them manually.
3. **Adding a new class**:
   - Add to `@layer components` in `resources/css/app.css`
   - Use `kebab-case` naming; group with similar classes
   - Include `dark:` variant if any color is set
   - Add a row to the relevant table in this file
4. **Flux UI** - use `<flux.*>` components for auth/settings pages. Use custom classes for application pages.

### Blade Example

```blade
<div class="card">
    <h2 class="section-title">Informations famille</h2>

    <p>
        <span class="detail-label">Nom :</span>
        <span class="detail-value">Dupont Marie</span>
    </p>

    <span class="badge--pending">À valider</span>

    <div class="notice-warning">
        Veuillez vérifier le justificatif.
    </div>

    <label class="field-label">Commentaire</label>
    <textarea class="field-input" wire:model="comment"></textarea>
    @error('comment') <p class="field-error">{{ $message }}</p> @enderror

    <div>
        <button class="btn-confirm" wire:click="validate">Valider</button>
        <button class="btn-warning" wire:click="askCorrection">Demander correction</button>
        <button class="btn-danger" wire:click="reject">Rejeter</button>
        <button class="btn-secondary" wire:click="skip">Passer</button>
    </div>
</div>
```

---

## Common Tasks

### Adding a new admin page
1. `php artisan make:livewire Admin/MyPage`
2. Add route in `routes/web.php` under `/admin` with role middleware
3. Create Blade view in `resources/views/livewire/admin/`
4. Add navigation link in `resources/views/layouts/app/sidebar.blade.php`

### Adding a new model
1. `php artisan make:model Name -mf`
2. Add `HasUuids` trait; use `$table->uuid('id')->primary()`
3. Define status constants as `public const STATUS_* = '...'`
4. Use `foreignUuid()->constrained()->cascadeOnDelete()` for relations

### Adding a new CSS class
1. Open `resources/css/app.css` - add inside `@layer components`
2. Use `@apply` with Tailwind utilities; include `dark:` variants
3. Document the class in the relevant table in this file
