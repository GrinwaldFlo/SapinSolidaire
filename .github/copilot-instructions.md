# SapinSolidaire - Agent Context Guide

## Mission And Product Scope

SapinSolidaire is a Laravel + Livewire application used by a charity workflow around holiday gift distribution.

Main actors:

- Families: request help via tokenized email link.
- Family validators: verify household eligibility.
- Validators: validate/reject children and requests.
- Organizers: generate labels, monitor lifecycle, send confirmations.
- Reception staff: mark gifts received and delivered.
- Admins: manage seasons, users/roles, settings, duplicate cleanup, dev tools.

Core lifecycle:

1. Family receives email token and submits/updates request.
2. Admin-side validation queues process family and child statuses.
3. Validated children get generated codes and printable labels.
4. Reception marks gifts as received.
5. Delivery marks gifts as given.
6. Confirmation emails are sent using assigned pickup slots.

## Tech Stack

- PHP 8.2+
- Laravel 12
- Livewire 4 (class-based components)
- Flux UI for auth/settings pages
- Tailwind CSS v4 via resources/css/app.css
- Vite 7
- Pest 4 test suite
- Laravel Fortify (auth + two-factor)
- barryvdh/laravel-dompdf (PDF generation)
- giggsey/libphonenumber-for-php (Swiss phone validation)

## Project Structure

High-value directories:

- app/Livewire/Family: public flow components (email entry + token form)
- app/Livewire/Admin: admin console features
- app/Livewire/Admin/Concerns: shared validation/rejection/duplicate logic
- app/Models: domain models (UUID PKs)
- app/Services: business services (season status, slot assignment, address/phone validation, duplicate detection)
- app/Mail: outgoing emails for access links, correction/rejection, confirmation
- routes/web.php: public + admin routes and role middleware
- routes/settings.php: profile/password/appearance/2FA routes
- database/migrations: full schema history and business-related schema evolution
- resources/views/livewire: paired views for Livewire components
- resources/views/pdf: label/monitoring PDF templates
- tests/Feature: auth, settings, family flow, model logic, validation behavior

## Runtime Commands

Development:

- composer run dev (serve + queue listener + vite)

Quality:

- composer lint
- composer test:lint
- composer test

Setup:

- composer run setup

## Authentication, Roles, And Access

Role constants are defined in app/Models/Role.php.

Roles:

- visitor
- validator
- validateFamily
- organizer
- reception
- admin

Middleware aliases are registered in bootstrap/app.php:

- role -> CheckRole
- any.role -> CheckAnyRole

Access patterns:

- /admin guarded by auth.
- Feature-level pages guarded by role and any.role middleware.
- Visitors with no operational roles are redirected to dashboard route.

Fortify behavior:

- First registered user becomes admin.
- Next users default to visitor.
- Login and two-factor are rate limited.

## Route Map

Public:

- / -> Family Home (request link form)
- /cadeau/{token} -> GiftRequestForm

Admin modules under /admin:

- Dashboard
- FamilyValidation
- Validation
- LabelGeneration
- GiftReception
- GiftDelivery
- ChildrenMonitoring
- SendConfirmations
- FamilyManagement
- FamilyDuplicates
- SeasonManagement
- UserManagement
- SettingsManagement
- ValidationMessageTemplates
- DevTools
- CssShowcase

Special admin file routes:

- proof_of_habitation response (local disk)
- generated PDF download (local disk)

## Domain Models And State Machines

GiftRequest:

- Status: pending, validated, rejected, rejected_final
- Linked to Family + Season, optional PickupSlot
- Holds family_number, proof path, slot_start/end datetime
- Mutation helper: setStatus(status, optional comment)

Child:

- Status: pending, validated, rejected, rejected_final, printed, received, given
- Gender: boy, girl, unspecified
- Code format: prefix + padded family_number + / + child_number
- assignChildNumberAndCode() is transactional and season counter-safe through GiftRequest/Season locks

Season:

- Defines request window and optional modification deadline
- Scheduling settings: family_limit_per_slot, slot_duration_minutes, responsible contact
- Atomic assignNextFamilyNumber() with lockForUpdate

Setting:

- Cached key/value configuration with helpers for domain settings
- Controls city whitelist, max ages/years, text blocks, code format, proof requirement, PDF style, validation templates

Other models:

- EmailToken: 48h access token for family form
- PickupSlot: pickup windows
- GeneratedPdf: generated label export history
- User/Role: many-to-many permissions

## Main Business Workflows

### Family request flow

1. Family submits email on Home component.
2. EmailToken is created and AccessLinkMail is queued.
3. GiftRequestForm validates token and active season.
4. Existing family/request data is loaded if present.
5. Address and phone are validated (Swiss services/libs).
6. Request/children are created or updated.
7. Optional proof of habitation upload is stored on local disk.

### Validation flow

FamilyValidation:

- Queue of pending family requests ordered by updated_at.
- Cache locks prevent two admins validating same request simultaneously.
- Can validate, reject, or final-reject with comment templates.

Validation:

- Handles both family and children decisions in one pass.
- Uses DB transaction and row-level locks for consistency.
- Assigns family numbers and child codes during validation.
- Sends correction/final rejection emails from shared concern trait.

### Label and distribution flow

- LabelGeneration selects validated children, marks them printed, generates PDF (label or grid style), stores file and DB trace.
- GiftReception marks printed children as received by family number keypad flow.
- GiftDelivery marks received children as given by family search.

### Confirmation flow

- SendConfirmations auto-assigns pickup sub-slots via SlotAssignmentService.
- Capacity summary compares available sub-slots vs families needing slots.
- Queues GiftReceivedMail and stamps confirmation_email_sent_at.

### Duplicate management flow

- FamilyDuplicateService computes similarity scores across families.
- FamilyDuplicates component runs scan on demand, caches pairs, supports merge with field-level overrides.
- Merge reattaches or consolidates gift requests/children per season.

## Services Overview

- SeasonService: current season status + overlap checks.
- SlotAssignmentService: lazy, capacity-aware assignment and recalculation of pickup slot windows.
- AddressValidationService: Swiss Post API validation.
- PhoneValidationService: CH parsing/validation/formatting.
- FamilyDuplicateService: duplicate scoring and merge transaction.
- CodeGeneratorService: legacy 4-letter code generator (current production flow uses numeric family/child pattern in Child model).

## Database And Migration Notes

Schema principles:

- UUID PK everywhere for domain tables.
- Cascade deletes on most foreign keys.
- No soft deletes.

Important evolution points:

- children.code moved from unique 4-char to nullable indexed varchar for family/child format.
- seasons gained next_family_number counter.
- pickup scheduling added with pickup_slots and slot_start/end on gift_requests.
- families.address split into street_name + house_no.
- gift_requests gained proof_of_habitation_path.
- generated_pdfs table tracks exports.
- users gained Fortify two-factor columns.

## Livewire Component Inventory

Family components:

- Home: email link request, season status messaging, throttling.
- GiftRequestForm: token gating, eligibility, family/children CRUD, proof upload, realtime validation.

Admin components:

- Dashboard: season-scoped KPI counters.
- FamilyValidation: household-level queue validation.
- Validation: family + child queue validation with lock and transaction semantics.
- LabelGeneration: status transition to printed + PDF generation/history.
- GiftReception: printed -> received transitions.
- GiftDelivery: received -> given transitions.
- ChildrenMonitoring: searchable/paginated child list + monitoring PDF export.
- SendConfirmations: slot computation + confirmation email queue.
- SeasonManagement: season CRUD + pickup window CRUD + overlap prevention.
- SettingsManagement: application settings + city validation + code regeneration on prefix/padding changes.
- UserManagement: role editing.
- FamilyManagement: searchable and sortable household view.
- FamilyDuplicates: duplicate scan + merge UI.
- ValidationMessageTemplates: reusable rejection/correction templates.
- DevTools: non-production helper operations (seed, batch transitions, cleanup, access links).
- CssShowcase: style preview for shared CSS classes.

## Mail And Queue Behavior

Mails are queued, not sent synchronously:

- AccessLinkMail
- CorrectionRequestMail
- FinalRejectionMail
- GiftReceivedMail

Queue worker is required in dev/prod for expected behavior.

## Test Coverage Snapshot

Tests are Pest feature-heavy and cover:

- Auth/registration/password/verification/two-factor pages and guards.
- Family form behavior including proof-of-habitation toggles.
- Settings behavior for city lists, proof flag, code prefix/padding.
- Model behavior for child code generation and season family numbering.
- Validation component behavior around code/number assignment.

When changing workflow logic, extend the corresponding Feature tests first.

## Non-Negotiable Conventions For Agents

1. Always use role constants from Role model.
2. Keep queries season-scoped unless intentionally cross-season.
3. Use model status constants, never raw status strings in new code.
4. Preserve transactional + lock behavior in validation and numbering paths.
5. Keep Livewire components lean; extract reusable logic into services/concerns.
6. Respect UUID assumptions in migrations, routing, and tests.
7. Use named routes.
8. Keep user-facing text translatable; French is the primary language.
9. Use shared CSS component classes in Blade (avoid inline utility drift).

## Risky Areas To Modify Carefully

- Validation queue locking (Cache keys and TTLs).
- Family number and child code assignment concurrency.
- Status transitions that trigger downstream UI/queries.
- Slot assignment math and capacity assumptions.
- Proof file storage paths and download routes.
- Duplicate merge behavior across same-season requests.

## CSS Styling Rules (Mandatory)

Never write raw Tailwind color/spacing/size utilities directly inside app Blade views.
Use shared component classes from resources/css/app.css.
If a class is missing:

1. Add it under @layer components.
2. Include dark variants if color is involved.
3. Document it in this guide.

Live visual reference remains available at /admin/css-showcase.

## Common Agent Tasks

Add an admin page:

1. php artisan make:livewire Admin/MyPage
2. Register route in routes/web.php under /admin with role middleware
3. Create or update corresponding Blade view in resources/views/livewire/admin
4. Add entry in resources/views/layouts/app/sidebar.blade.php

Add domain model/migration:

1. Use UUID primary key and foreignUuid constraints.
2. Add status constants where relevant.
3. Add relationships and casts explicitly.
4. Add/adjust Pest Feature tests for behavior.

Modify validation or lifecycle logic:

1. Update statuses via model methods.
2. Preserve transaction + lock guarantees.
3. Verify all downstream list filters (dashboard/reception/delivery/confirmations).
4. Run composer test.
