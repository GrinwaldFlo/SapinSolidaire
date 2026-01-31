# Database Structure Specification

## Overview

This document describes the database schema for the **Sapin Solidaire** application. The database uses **MariaDB** and follows Laravel conventions for naming and structure.

---

## Tables

### 1. `users`

Staff members who manage the application. Based on Laravel's default user table with extensions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(255) | NOT NULL | Full name |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Email address |
| `email_verified_at` | TIMESTAMP | NULLABLE | Email verification date |
| `password` | VARCHAR(255) | NOT NULL | Hashed password |
| `two_factor_secret` | TEXT | NULLABLE | 2FA secret (Laravel Fortify) |
| `two_factor_recovery_codes` | TEXT | NULLABLE | 2FA recovery codes |
| `two_factor_confirmed_at` | TIMESTAMP | NULLABLE | 2FA confirmation date |
| `remember_token` | VARCHAR(100) | NULLABLE | Remember me token |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Update timestamp |

**Indexes:**
- PRIMARY (`id`)
- UNIQUE (`email`)

---

### 2. `roles`

Available roles for staff members.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(50) | NOT NULL, UNIQUE | Role name |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Update timestamp |

**Predefined values:**
- `visitor` (Visiteur)
- `validator` (Validateur)
- `organizer` (Organisateur)
- `reception` (Accueil)
- `admin` (Admin)

**Indexes:**
- PRIMARY (`id`)
- UNIQUE (`name`)

---

### 3. `role_user`

Pivot table for many-to-many relationship between users and roles.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `user_id` | BIGINT UNSIGNED | FK → users.id, NOT NULL | Reference to user |
| `role_id` | BIGINT UNSIGNED | FK → roles.id, NOT NULL | Reference to role |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Update timestamp |

**Indexes:**
- PRIMARY (`id`)
- UNIQUE (`user_id`, `role_id`)
- INDEX (`user_id`)
- INDEX (`role_id`)

**Foreign Keys:**
- `user_id` → `users.id` ON DELETE CASCADE
- `role_id` → `roles.id` ON DELETE CASCADE

---

### 4. `seasons`

Gift distribution seasons/campaigns.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(255) | NOT NULL | Season name (e.g., "Noël 2024") |
| `start_date` | DATE | NOT NULL | Start date for requests |
| `end_date` | DATE | NOT NULL | End date for requests |
| `modification_deadline` | DATE | NULLABLE | Deadline for family modifications |
| `pickup_start_date` | DATE | NULLABLE | Date from which gifts can be picked up |
| `pickup_address` | TEXT | NULLABLE | Address for gift pickup |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Update timestamp |

**Indexes:**
- PRIMARY (`id`)
- INDEX (`start_date`, `end_date`)

**Business Rules:**
- Season dates must not overlap with other seasons
- Only one season can be active at a time

---

### 5. `families`

Family information (persistent across seasons).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Family email (identifier) |
| `first_name` | VARCHAR(255) | NULLABLE | Parent first name |
| `last_name` | VARCHAR(255) | NULLABLE | Parent last name |
| `address` | VARCHAR(255) | NULLABLE | Street address |
| `postal_code` | VARCHAR(10) | NULLABLE | Postal code |
| `city` | VARCHAR(255) | NULLABLE | City |
| `npa` | VARCHAR(10) | NULLABLE | NPA (Swiss postal code) |
| `phone` | VARCHAR(20) | NULLABLE | Phone number (Swiss format) |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Update timestamp |

**Indexes:**
- PRIMARY (`id`)
- UNIQUE (`email`)

---

### 6. `gift_requests`

Family gift requests per season (one per family per season).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `family_id` | BIGINT UNSIGNED | FK → families.id, NOT NULL | Reference to family |
| `season_id` | BIGINT UNSIGNED | FK → seasons.id, NOT NULL | Reference to season |
| `status` | ENUM | NOT NULL, DEFAULT 'pending' | Request status |
| `status_changed_at` | TIMESTAMP | NULLABLE | Last status change date |
| `rejection_comment` | TEXT | NULLABLE | Reason for rejection |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Update timestamp |

**Status values:**
- `pending` (À valider)
- `validated` (Validé)
- `rejected` (Refusé - correction requested)
- `rejected_final` (Refusé définitivement)

**Indexes:**
- PRIMARY (`id`)
- UNIQUE (`family_id`, `season_id`)
- INDEX (`family_id`)
- INDEX (`season_id`)
- INDEX (`status`)

**Foreign Keys:**
- `family_id` → `families.id` ON DELETE CASCADE
- `season_id` → `seasons.id` ON DELETE CASCADE

---

### 7. `children`

Child gift requests linked to a family and season.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `gift_request_id` | BIGINT UNSIGNED | FK → gift_requests.id, NOT NULL | Reference to gift request |
| `first_name` | VARCHAR(255) | NOT NULL | Child's first name |
| `birth_year` | SMALLINT UNSIGNED | NOT NULL | Year of birth |
| `height` | SMALLINT UNSIGNED | NULLABLE | Height in cm |
| `gift` | VARCHAR(255) | NOT NULL | Requested gift |
| `shoe_size` | VARCHAR(10) | NULLABLE | Shoe size (if shoes requested) |
| `code` | CHAR(4) | NOT NULL, UNIQUE | Unique 4-letter code |
| `status` | ENUM | NOT NULL, DEFAULT 'pending' | Child request status |
| `status_changed_at` | TIMESTAMP | NULLABLE | Last status change date |
| `rejection_comment` | TEXT | NULLABLE | Reason for rejection |
| `validated_at` | TIMESTAMP | NULLABLE | Validation timestamp (for sorting labels) |
| `confirmation_email_sent_at` | TIMESTAMP | NULLABLE | Last confirmation email date |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Update timestamp |

**Status values:**
- `pending` (À valider)
- `validated` (Validé)
- `rejected` (Refusé - correction requested)
- `rejected_final` (Refusé définitivement)
- `printed` (Imprimé)
- `received` (Reçu)
- `given` (Donné)

**Indexes:**
- PRIMARY (`id`)
- UNIQUE (`code`)
- INDEX (`gift_request_id`)
- INDEX (`status`)
- INDEX (`validated_at`)

**Foreign Keys:**
- `gift_request_id` → `gift_requests.id` ON DELETE CASCADE

---

### 8. `email_tokens`

Temporary tokens for family email verification.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `email` | VARCHAR(255) | NOT NULL | Family email |
| `token` | VARCHAR(64) | NOT NULL, UNIQUE | Unique verification token |
| `expires_at` | TIMESTAMP | NOT NULL | Token expiration (48h) |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Update timestamp |

**Indexes:**
- PRIMARY (`id`)
- UNIQUE (`token`)
- INDEX (`email`)
- INDEX (`expires_at`)

---

### 9. `settings`

Application configuration settings.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `key` | VARCHAR(100) | NOT NULL, UNIQUE | Setting key |
| `value` | TEXT | NULLABLE | Setting value |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Update timestamp |

**Predefined keys:**
- `site_name` - Site name
- `allowed_postal_codes` - Comma-separated list of allowed postal codes
- `max_consecutive_years` - Max years a family can request (default: 3)
- `gift_suggestions` - Multi-line text of gift suggestions
- `introduction_text` - Welcome text for families
- `reply_to_email` - Reply-to address for emails

**Indexes:**
- PRIMARY (`id`)
- UNIQUE (`key`)

---

### 10. `jobs` (Laravel Queue)

Standard Laravel jobs table for email queue.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `queue` | VARCHAR(255) | NOT NULL | Queue name |
| `payload` | LONGTEXT | NOT NULL | Serialized job |
| `attempts` | TINYINT UNSIGNED | NOT NULL | Attempt count |
| `reserved_at` | INT UNSIGNED | NULLABLE | Reserved timestamp |
| `available_at` | INT UNSIGNED | NOT NULL | Available timestamp |
| `created_at` | INT UNSIGNED | NOT NULL | Creation timestamp |

**Indexes:**
- PRIMARY (`id`)
- INDEX (`queue`)

---

### 11. `failed_jobs` (Laravel Queue)

Standard Laravel failed jobs table.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `uuid` | VARCHAR(255) | UNIQUE | Job UUID |
| `connection` | TEXT | NOT NULL | Connection name |
| `queue` | TEXT | NOT NULL | Queue name |
| `payload` | LONGTEXT | NOT NULL | Serialized job |
| `exception` | LONGTEXT | NOT NULL | Exception details |
| `failed_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Failure timestamp |

---

## Relationships Summary

| Parent Table | Child Table | Relationship | Description |
|--------------|-------------|--------------|-------------|
| `users` | `role_user` | One-to-Many | User can have multiple roles |
| `roles` | `role_user` | One-to-Many | Role can be assigned to multiple users |
| `seasons` | `gift_requests` | One-to-Many | Season has many gift requests |
| `families` | `gift_requests` | One-to-Many | Family can request gifts in multiple seasons |
| `gift_requests` | `children` | One-to-Many | Request contains multiple children |

---

## Business Rules Summary

### Data Integrity
1. One gift request per family per season (UNIQUE constraint)
2. Child code must be unique across all records (4 uppercase letters)
3. Season dates must not overlap
4. Email tokens expire after 48 hours

### Status Workflow

**Gift Request Status:**
```
pending → validated
        → rejected → pending (after correction)
        → rejected_final
```

**Child Status:**
```
pending → validated → printed → received → given
        → rejected → pending (after correction)
        → rejected_final
```

### Modification Rules
- Family info: Modifiable until `modification_deadline`
- Child preferences: Modifiable only if status is `pending`, `rejected`, or `validated`

---

## Environment Variables

```env
# Email rate limiting (default: 1 email per 5 seconds)
MAIL_RATE_LIMIT_SECONDS=5
```

---

## Notes

- All timestamps use the database server timezone
- Soft deletes are NOT used; data is retained for statistics
- The `code` field in `children` table uses 4 uppercase letters (26^4 = 456,976 combinations)
- Phone validation should follow Swiss format (+41 or 0xx xxx xx xx)
