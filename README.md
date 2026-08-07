# OSA Activity Request & Document Tracking System

Digitizes how student organizations at Ateneo de Zamboanga University request, submit, and track
documentation for in-campus and off-campus activities under the Office of Student Affairs (OSA).

The system does **not** replace the university's wet-signature approval chain — it digitizes the
document preparation, submission, and status-tracking layer. Physical-only items (Pink/Blue forms,
Parent's Consent, Certificate of Compliance, Medical Certificate) are tracked as checklist items but
never uploaded.

See `docs/` for the full scope, architecture, and design system.

## Stack

- **Laravel 13** (PHP 8.3+) · Blade + **Tailwind CSS v3** · **MySQL** (XAMPP)
- Auth: Laravel Breeze (Blade). Roles via a `users.role` enum + `role` middleware.
- PDF: `barryvdh/laravel-dompdf` · Icons: `blade-ui-kit/blade-heroicons` · Tests: Pest

## Roles & workflow

`org_officer` submits a request → `moderator` endorses (and recommends OSA Form 3 for off-campus)
→ `osa_admin` reviews and verifies the checklist → auto-advances to *docs complete* → `osa_director`
notes it → *awaiting physical* → admin generates a reference slip, records the wet-signed outcome,
and marks it *approved*.

## Local setup (XAMPP)

1. Start **MySQL** in the XAMPP control panel and create the database:
   ```sql
   CREATE DATABASE osa_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Install dependencies and prepare the app:
   ```bash
   composer install
   cp .env.example .env    # then set DB_DATABASE=osa_system, DB_USERNAME=root, DB_PASSWORD=
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   npm install && npm run build
   ```
3. Serve:
   ```bash
   php artisan serve
   ```

The `.env` already ships pointed at `127.0.0.1:3306` / `osa_system` / `root` with an empty password.

## Demo logins

After `php artisan migrate:fresh --seed`, all accounts use the password **`password`**:

| Role | Email |
|---|---|
| Organization Officer | `org1@osa.test`, `org2@osa.test`, `org3@osa.test` |
| Moderator | `mod1@osa.test`, `mod2@osa.test` |
| OSA Admin | `admin@osa.test` |
| OSA Director | `director@osa.test` |

The seeder creates 3 organizations and 5 sample requests spanning every pipeline stage
(submitted → moderator endorsed → OSA reviewing → docs complete → approved).

## Tests

```bash
php artisan test
```

Tests run against an in-memory SQLite database (configured in `phpunit.xml`), so they don't touch
your MySQL data.
