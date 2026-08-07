# OSA Activity Request & Document Tracking System — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Laravel monolith that lets AdZU student orgs submit digital activity-request packets, routes them through moderator endorsement → OSA review → Director notation, tracks physical-only documents as checklist items, and generates reference slips/PDFs for the physical signing stage.

**Architecture:** Server-rendered Laravel MVC (Blade + Tailwind), thin controllers delegating to service classes (`ActivityRequestService`, `ChecklistService`, `EndorsementService`, `ReferenceSlipService`, `PdfExportService`), role-based access via a `role` enum column + middleware, MySQL storage, local-disk file uploads. Full spec: `docs/System Architecture.md`, `docs/OSA System - Scope and Overview.md`, `docs/Design System.md`.

**Tech Stack:** PHP 8.3+, Laravel 12, Laravel Breeze (Blade stack), Tailwind CSS (v4, bundled with Breeze), MySQL (XAMPP), Pest (tests), `barryvdh/laravel-dompdf` (PDFs), `blade-ui-kit/blade-heroicons` (icons). Alpine.js ships with Breeze — use it for small interactions (dropdowns, notification bell); no SPA framework.

## Global Constraints

- Monolithic server-rendered app: controllers return Blade views. No API layer, no Vue/React/Livewire/Inertia.
- Roles are exactly: `org_officer`, `moderator`, `osa_admin`, `osa_director` (users.role enum). Keep the plain enum column — do **not** install `spatie/laravel-permission` in v1 (architecture doc allows either; YAGNI wins).
- `ACTIVITY_REQUESTS.status` enum values verbatim: `draft, submitted, moderator_endorsed, osa_reviewing, docs_complete, awaiting_physical, approved, denied, revision_needed`.
- `DOCUMENT_UPLOADS.document_type` enum verbatim: `moderator_id, dean_id, parent_id, student_id, participant_list_file, schedule_file`. Parent's Consent and Medical Certificate must NEVER be uploadable — checklist-only with `is_physical = true`.
- Certificate of Compliance is one checklist item bundled with the Blue Form, never a separate item.
- OSA Form 3 has a single digital approval: the moderator's recommending approval. No Dean/Director/VP chain.
- 3-day rule: activity `date_start` must be ≥ 3 days after submission date (enforced in validation; OSA Admin sees late flags).
- Design tokens verbatim from `docs/Design System.md` §1 (navy/gold/paper/ink palette, Inter/Montserrat/Playfair fonts). Gold is accent-only — never a button fill or large surface. Sentence case for standard UI copy.
- DB: MySQL `osa_system` at `127.0.0.1:3306`, user `root`, empty password (XAMPP). Tests use SQLite in-memory.
- Uploads: `storage/app/public` via `php artisan storage:link`; max 5 MB; mimes `pdf,jpg,jpeg,png`.
- Test runner: `php artisan test` (Pest). Commit after every green task.

## File Structure (locked decomposition)

```
app/
├── Enums/ActivityStatus.php · Role.php · DocumentType.php · ChecklistStatus.php · ApprovalStage.php · Decision.php
├── Http/
│   ├── Controllers/
│   │   ├── Org/ActivityRequestController.php · Org/DocumentUploadController.php · Org/OsaForm3Controller.php
│   │   ├── Moderator/EndorsementController.php · Moderator/OsaForm3ApprovalController.php
│   │   ├── OsaAdmin/ReviewQueueController.php · OsaAdmin/ChecklistController.php
│   │   ├── OsaAdmin/OrganizationController.php · OsaAdmin/UserController.php · OsaAdmin/ModeratorAssignmentController.php
│   │   ├── OsaDirector/NotationController.php
│   │   ├── ReferenceSlipController.php · ArchiveController.php · NotificationController.php · DashboardController.php
│   ├── Requests/StoreActivityRequestRequest.php · UploadDocumentRequest.php · StoreOsaForm3Request.php · DecisionRequest.php
│   └── Middleware/EnsureUserHasRole.php
├── Models/ Organization · User · ActivityRequest · Participant · ScheduleItem · DocumentUpload
│          · ChecklistItem · Approval · OsaForm3 · OsaForm3ComplianceItem · ReferenceSlip
├── Policies/ActivityRequestPolicy.php · OsaForm3Policy.php
├── Services/ActivityRequestService.php · ChecklistService.php · EndorsementService.php
│           · ReferenceSlipService.php · PdfExportService.php
├── Notifications/ NewRequestAwaitingEndorsement · DocumentsMissing · PacketReadyForPhysicalStage · StatusChanged
database/migrations/ (Tasks 2 & 4) · database/seeders/DemoSeeder.php
resources/views/ layouts/app.blade.php · components/{status-pill,file-card,hero-panel,eyebrow-badge,timeline}.blade.php
  org/{dashboard,requests/create,requests/show,osa-form-3}.blade.php
  moderator/{queue,show,osa-form-3}.blade.php
  osa-admin/{queue,show,organizations/*,users/*}.blade.php
  osa-director/{queue,show}.blade.php
  archive/index.blade.php · pdf/{reference-slip,osa-form-3}.blade.php
routes/web.php
```

---

### Task 1: Project scaffold & tooling

**Files:**
- Create: fresh Laravel app at repo root (keep existing `docs/`), `.env`, `resources/css/app.css` token layer
- Modify: `composer.json` (dompdf, heroicons), `phpunit.xml` (SQLite for tests)
- Test: `tests/Feature/SmokeTest.php`

**Interfaces:**
- Consumes: nothing (first task)
- Produces: bootable app, `php artisan test` green, Tailwind tokens (`navy-900/800/700`, `slate-500/200`, `gold-500/700`, `paper`, `paper-muted`, `ink-900`, `bg-navy-gradient`, font families `sans`/`display`/`wordmark`) usable in all later Blade views.

- [ ] **Step 1: Scaffold Laravel + Breeze into the repo root**

```bash
cd /Users/tatelgabrielle/Desktop/PROJECTS/ALP/osa-system
composer create-project laravel/laravel tmp-app
# move app into repo root without clobbering docs/
rsync -a tmp-app/ ./ && rm -rf tmp-app
composer require laravel/breeze --dev
php artisan breeze:install blade --pest
npm install && npm run build
composer require barryvdh/laravel-dompdf blade-ui-kit/blade-heroicons
```

- [ ] **Step 2: Configure `.env` for XAMPP MySQL and tests for SQLite**

`.env`:
```
APP_NAME="OSA System"
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=osa_system
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
```

In `phpunit.xml`, uncomment/set:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Create the MySQL DB (XAMPP MySQL must be running): `mysql -u root -e "CREATE DATABASE IF NOT EXISTS osa_system"` (or via phpMyAdmin), then `php artisan migrate` and `php artisan storage:link`.

- [ ] **Step 3: Add design tokens to Tailwind (v4 CSS-first config)**

Breeze on Laravel 12 uses Tailwind v4 — tokens go in `resources/css/app.css`, not `tailwind.config.js`:

```css
@import 'tailwindcss';
@source '../views';

@theme {
    --color-navy-900: #0B1930;
    --color-navy-800: #142A4D;
    --color-navy-700: #1B3868;
    --color-slate-500: #5C77A6;
    --color-slate-200: #C9D6EA;
    --color-gold-500: #D4AF37;
    --color-gold-700: #7A5C14;
    --color-paper: #FFFFFF;
    --color-paper-muted: #F4F6FA;
    --color-ink-900: #16233F;
    --font-sans: 'Inter', 'Poppins', ui-sans-serif, system-ui;
    --font-display: 'Montserrat', ui-sans-serif;
    --font-wordmark: 'Playfair Display', serif;
}
.bg-navy-gradient { background-image: linear-gradient(180deg, #1B3868 0%, #0B1930 100%); }
```

(If the installed Breeze still ships `tailwind.config.js` / Tailwind v3, instead paste the `theme.extend` block from `docs/Design System.md` §1 into it — same tokens either way.)

Add fonts to the Breeze layout `<head>` (`resources/views/layouts/app.blade.php` and `guest.blade.php`):
```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;800;900&family=Playfair+Display:ital@1&display=swap" rel="stylesheet">
```

- [ ] **Step 4: Write and run the smoke test**

`tests/Feature/SmokeTest.php`:
```php
<?php
it('serves the login page', function () {
    $this->get('/login')->assertOk();
});
```
Run: `php artisan test` — expect all green (Breeze ships auth tests too).

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "chore: scaffold Laravel 12 + Breeze + design tokens + dompdf/heroicons"
```

---

### Task 2: Roles, organizations table, and role middleware

**Files:**
- Create: `app/Enums/Role.php`, `database/migrations/*_create_organizations_table.php`, `*_add_role_and_organization_to_users_table.php`, `*_create_organization_moderator_table.php`, `app/Http/Middleware/EnsureUserHasRole.php`, `app/Models/Organization.php`, `database/factories/OrganizationFactory.php`
- Modify: `app/Models/User.php`, `bootstrap/app.php` (middleware alias), `database/factories/UserFactory.php`, `routes/web.php`, `app/Http/Controllers/DashboardController.php` (create)
- Test: `tests/Feature/RoleMiddlewareTest.php`

**Interfaces:**
- Consumes: Breeze `users` table/auth.
- Produces: `Role` string-backed enum (`OrgOfficer='org_officer'`, `Moderator='moderator'`, `OsaAdmin='osa_admin'`, `OsaDirector='osa_director'`); `User::role` (casts to `Role`), `User::organization()` BelongsTo, `User::moderatedOrganizations()` BelongsToMany; `Organization::officers()` HasMany, `Organization::moderators()` BelongsToMany; route middleware alias `role` (usage `->middleware('role:osa_admin')`); named role dashboards `org.dashboard`, `moderator.queue`, `osa-admin.queue`, `osa-director.queue` (placeholder routes now, real pages in later tasks); `/dashboard` redirects per role.

- [ ] **Step 1: Write failing tests**

`tests/Feature/RoleMiddlewareTest.php`:
```php
<?php
use App\Enums\Role;
use App\Models\User;

it('blocks the wrong role', function () {
    $officer = User::factory()->create(['role' => Role::OrgOfficer]);
    $this->actingAs($officer)->get('/osa-admin/queue')->assertForbidden();
});

it('allows the right role', function () {
    $admin = User::factory()->create(['role' => Role::OsaAdmin]);
    $this->actingAs($admin)->get('/osa-admin/queue')->assertOk();
});

it('redirects /dashboard by role', function () {
    $mod = User::factory()->create(['role' => Role::Moderator]);
    $this->actingAs($mod)->get('/dashboard')->assertRedirect(route('moderator.queue'));
});
```
Run: `php artisan test --filter=RoleMiddleware` — expect FAIL (enum/routes missing).

- [ ] **Step 2: Migrations**

`create_organizations_table`:
```php
Schema::create('organizations', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('accreditation_status')->default('accredited'); // accredited | suspended | inactive
    $table->timestamps();
});
```
`add_role_and_organization_to_users_table`:
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('role')->default('org_officer');
    $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
});
```
`create_organization_moderator_table`:
```php
Schema::create('organization_moderator', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the moderator
    $table->unique(['organization_id', 'user_id']);
});
```

- [ ] **Step 3: Enum, models, factories**

`app/Enums/Role.php`:
```php
<?php
namespace App\Enums;

enum Role: string
{
    case OrgOfficer = 'org_officer';
    case Moderator = 'moderator';
    case OsaAdmin = 'osa_admin';
    case OsaDirector = 'osa_director';
}
```
`User.php` additions: `protected function casts(): array { return [... existing, 'role' => Role::class]; }`, add `'role','organization_id'` to `$fillable`, and:
```php
public function organization() { return $this->belongsTo(Organization::class); }
public function moderatedOrganizations() { return $this->belongsToMany(Organization::class, 'organization_moderator'); }
```
`Organization.php`:
```php
class Organization extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'accreditation_status'];
    public function officers() { return $this->hasMany(User::class); }
    public function moderators() { return $this->belongsToMany(User::class, 'organization_moderator'); }
    public function activityRequests() { return $this->hasMany(ActivityRequest::class); }
}
```
(`activityRequests()` references Task 4's model — add the method in Task 4 if you prefer strict ordering.)
`OrganizationFactory`: `['name' => fake()->company(), 'accreditation_status' => 'accredited']`. `UserFactory`: add `'role' => Role::OrgOfficer`.

- [ ] **Step 4: Middleware + routes + dashboard redirect**

`EnsureUserHasRole.php`:
```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        abort_unless(in_array($request->user()?->role?->value, $roles, true), 403);
        return $next($request);
    }
}
```
`bootstrap/app.php`: `->withMiddleware(fn ($m) => $m->alias(['role' => EnsureUserHasRole::class]))`.

`DashboardController.php`:
```php
public function __invoke(Request $request)
{
    return redirect()->route(match ($request->user()->role) {
        Role::OrgOfficer => 'org.dashboard',
        Role::Moderator => 'moderator.queue',
        Role::OsaAdmin => 'osa-admin.queue',
        Role::OsaDirector => 'osa-director.queue',
    });
}
```
`routes/web.php` — replace Breeze's `/dashboard` view route with the redirect controller, and add placeholder role home routes (return a bare Blade view for now; later tasks replace them):
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::view('/org', 'org.dashboard')->middleware('role:org_officer')->name('org.dashboard');
    Route::view('/moderator/queue', 'moderator.queue')->middleware('role:moderator')->name('moderator.queue');
    Route::view('/osa-admin/queue', 'osa-admin.queue')->middleware('role:osa_admin')->name('osa-admin.queue');
    Route::view('/osa-director/queue', 'osa-director.queue')->middleware('role:osa_director')->name('osa-director.queue');
});
```
Create four minimal placeholder views (e.g. `resources/views/org/dashboard.blade.php` containing `<x-app-layout><div class="p-6">Org dashboard</div></x-app-layout>`). Also disable public registration (accounts are provisioned by OSA Admin — Task 14): remove the register routes by passing `Route::middleware('guest')` edits in `routes/auth.php` or simply delete the `register` route entries and the "Register" links in `login.blade.php`/`welcome.blade.php`.

- [ ] **Step 5: Run tests, then commit**

Run: `php artisan test` — expect PASS.
```bash
git add -A && git commit -m "feat: roles, organizations, role middleware, per-role dashboard routing"
```

---

### Task 3: Design-system Blade components

**Files:**
- Create: `resources/views/components/hero-panel.blade.php`, `status-pill.blade.php`, `file-card.blade.php`, `eyebrow-badge.blade.php`, `timeline.blade.php`
- Modify: `resources/views/layouts/app.blade.php` (paper-muted background, role-aware nav)
- Test: `tests/Feature/ComponentsTest.php`

**Interfaces:**
- Consumes: Tailwind tokens (Task 1).
- Produces: `<x-hero-panel :title="" :reference="">`, `<x-status-pill :status="">` (accepts any `ACTIVITY_REQUESTS`/`CHECKLIST_ITEMS` status string), `<x-file-card :label="" :physical="" :status="">` (slot for actions), `<x-eyebrow-badge>`, `<x-timeline :current="">` (renders the 7-stage org status flow). All later views compose these.

- [ ] **Step 1: Failing render test**

```php
<?php
use Illuminate\Support\Facades\Blade;

it('renders a status pill with the mapped color', function () {
    $html = Blade::render('<x-status-pill status="moderator_endorsed" />');
    expect($html)->toContain('Moderator endorsed')->toContain('bg-blue-50');
});

it('renders a physical file card with amber pill and no upload slot needed', function () {
    $html = Blade::render('<x-file-card label="Parent\'s Consent" :physical="true" status="pending" />');
    expect($html)->toContain("Parent's Consent")->toContain('Physical');
});
```
Run: `php artisan test --filter=Components` — FAIL (components missing).

- [ ] **Step 2: Implement components**

`status-pill.blade.php` (single source of truth for status labels/colors):
```blade
@props(['status'])
@php
    $map = [
        'draft' => 'bg-gray-100 text-gray-600', 'pending' => 'bg-amber-50 text-amber-700',
        'submitted' => 'bg-blue-50 text-blue-700', 'moderator_endorsed' => 'bg-blue-50 text-blue-700',
        'osa_reviewing' => 'bg-blue-50 text-blue-700', 'docs_complete' => 'bg-green-50 text-green-700',
        'awaiting_physical' => 'bg-amber-50 text-amber-700', 'approved' => 'bg-green-50 text-green-700',
        'verified' => 'bg-green-50 text-green-700', 'denied' => 'bg-red-50 text-red-700',
        'revision_needed' => 'bg-red-50 text-red-700', 'rejected' => 'bg-red-50 text-red-700',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-block text-[11px] font-medium px-2.5 py-1 rounded-full '.($map[$status] ?? 'bg-gray-100 text-gray-600')]) }}>
    {{ ucfirst(str_replace('_', ' ', $status)) }}
</span>
```
`file-card.blade.php` (folder-tab motif from Design System §3.2):
```blade
@props(['label', 'physical' => false, 'status' => 'pending'])
<div class="relative pt-3.5">
    <div class="absolute top-0 left-4 w-16 h-4 bg-slate-500 rounded-t-md"></div>
    <div class="relative bg-paper border border-slate-200 rounded-md p-4">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-ink-900">{{ $label }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ $physical ? 'Physical' : 'Digital' }}</p>
            </div>
            <x-heroicon-o-paper-clip class="w-4 h-4 text-slate-500 rotate-12" />
        </div>
        <x-status-pill :status="$status" class="mt-2" />
        <div class="mt-3">{{ $slot }}</div>
    </div>
</div>
```
`hero-panel.blade.php`:
```blade
@props(['title', 'reference' => null])
<div class="bg-navy-gradient rounded-xl px-6 py-5 text-white">
    <div class="flex items-center gap-2 mb-3">
        <span class="w-7 h-7 rounded-full border-2 border-gold-500 flex items-center justify-center">
            <x-heroicon-o-pencil class="w-3.5 h-3.5 text-gold-500" />
        </span>
        <span class="text-sm font-medium">Office of Student Affairs</span>
    </div>
    @if($reference)<p class="text-xs italic text-gold-500 tracking-wide mb-1">Ref. {{ $reference }}</p>@endif
    <h1 class="font-display text-xl font-extrabold uppercase tracking-wide">{{ $title }}</h1>
    <div>{{ $slot }}</div>
</div>
```
`eyebrow-badge.blade.php`:
```blade
<span {{ $attributes->merge(['class' => 'text-[11px] font-semibold uppercase tracking-wide px-3 py-1 rounded-full border border-gold-500 text-gold-700']) }}>{{ $slot }}</span>
```
`timeline.blade.php`:
```blade
@props(['current'])
@php
    $stages = ['submitted' => 'Submitted', 'moderator_endorsed' => 'Moderator endorsed',
        'osa_reviewing' => 'OSA reviewing', 'docs_complete' => 'Digital docs complete',
        'awaiting_physical' => 'Awaiting physical forms', 'approved' => 'Approved'];
    $keys = array_keys($stages);
    $idx = array_search($current, $keys); // false for draft/denied/revision_needed
@endphp
<ol class="flex flex-wrap gap-y-2 items-center text-xs">
    @foreach($stages as $key => $label)
        @php $done = $idx !== false && array_search($key, $keys) <= $idx; @endphp
        <li class="flex items-center gap-1.5 {{ $loop->last ? '' : 'after:content-[\'\'] after:w-6 after:h-px after:bg-slate-200 after:mx-2' }}">
            <span class="w-2 h-2 rounded-full {{ $done ? 'bg-gold-500' : 'bg-slate-200' }}"></span>
            <span class="{{ $done ? 'text-ink-900 font-medium' : 'text-gray-400' }}">{{ $label }}</span>
        </li>
    @endforeach
    @if(in_array($current, ['denied', 'revision_needed']))<li class="ml-3"><x-status-pill :status="$current" /></li>@endif
</ol>
```
In `layouts/app.blade.php`: set `<body class="font-sans antialiased bg-paper-muted text-ink-900">`, and in the nav include links conditionally by `auth()->user()->role` (org: Dashboard/New request; moderator: Endorsement queue/OSA Form 3; osa_admin: Review queue/Organizations/Users/Archive; osa_director: Notation queue; all: Archive appears for OSA roles only).

- [ ] **Step 3: Run tests, commit**

Run: `php artisan test --filter=Components` — PASS.
```bash
git add -A && git commit -m "feat: design-system Blade components (hero, status pill, file card, timeline)"
```

---

### Task 4: Domain schema & models

**Files:**
- Create: migrations `*_create_activity_requests_table.php`, `*_create_participants_table.php`, `*_create_schedule_items_table.php`, `*_create_document_uploads_table.php`, `*_create_checklist_items_table.php`, `*_create_approvals_table.php`, `*_create_osa_form3s_table.php`, `*_create_osa_form3_compliance_items_table.php`, `*_create_reference_slips_table.php`
- Create: `app/Enums/{ActivityStatus,DocumentType,ChecklistStatus,ApprovalStage,Decision}.php`, models `ActivityRequest, Participant, ScheduleItem, DocumentUpload, ChecklistItem, Approval, OsaForm3, OsaForm3ComplianceItem, ReferenceSlip`, `database/factories/ActivityRequestFactory.php`
- Test: `tests/Feature/SchemaTest.php`

**Interfaces:**
- Consumes: `Organization`, `User` (Task 2).
- Produces: all Eloquent models with relations exactly as the ERD in `docs/System Architecture.md` §4. Key signatures later tasks rely on:
  - `ActivityRequest`: casts `activity_type` (plain string `in_campus|off_campus`), `status => ActivityStatus`, dates; relations `organization()`, `submitter()`, `participants()`, `scheduleItems()`, `documentUploads()`, `checklistItems()`, `approvals()`, `osaForm3()` (HasOne), `referenceSlip()` (HasOne); scope `scopeStatus($q, ActivityStatus $s)`.
  - Enums: `ActivityStatus` (9 cases mirroring the status list), `DocumentType` (6 cases), `ChecklistStatus` (`Pending|Submitted|Verified` = `pending|submitted|verified`), `ApprovalStage` (`ModeratorEndorsement|OsaReview|OsaDirectorNotation` = `moderator_endorsement|osa_review|osa_director_notation`), `Decision` (`Approved|Rejected|RevisionRequested` = `approved|rejected|revision_requested`).
  - `ActivityRequestFactory` defaults: linked org + org-officer submitter, `activity_type => 'in_campus'`, `status => ActivityStatus::Submitted`, future dates.

- [ ] **Step 1: Failing schema test**

```php
<?php
use App\Enums\ActivityStatus;
use App\Models\{ActivityRequest, ChecklistItem};

it('creates a request with relations', function () {
    $request = ActivityRequest::factory()->create();
    $request->checklistItems()->create(['item_name' => 'List of Participants', 'is_physical' => false]);
    expect($request->organization)->not->toBeNull()
        ->and($request->status)->toBe(ActivityStatus::Submitted)
        ->and($request->checklistItems)->toHaveCount(1)
        ->and(ChecklistItem::first()->status->value)->toBe('pending');
});
```
Run — FAIL.

- [ ] **Step 2: Migrations (column lists copied from the ERD)**

`activity_requests`:
```php
Schema::create('activity_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->foreignId('submitted_by')->constrained('users');
    $table->string('activity_type'); // in_campus | off_campus
    $table->string('title');
    $table->string('nature_of_activity');
    $table->string('nature_of_engagement'); // organizer | partner | participant
    $table->string('main_organizer')->nullable();
    $table->date('date_start');
    $table->date('date_end');
    $table->time('time_of_activity');
    $table->string('venue');
    $table->text('purpose');
    $table->string('status')->default('draft');
    $table->timestamp('submitted_at')->nullable();
    $table->timestamps();
});
```
`participants`: `id, activity_request_id FK cascadeOnDelete, full_name, year_course nullable, contact_info nullable, timestamps`.
`schedule_items`: `id, activity_request_id FK cascade, time_slot, description, timestamps`.
`document_uploads`: `id, activity_request_id FK cascade, document_type string, file_path, uploaded_by FK users, uploaded_at timestamp useCurrent, timestamps`.
`checklist_items`: `id, activity_request_id FK cascade, item_name, is_physical boolean default false, status string default 'pending', notes nullable, timestamps`.
`approvals`: `id, activity_request_id FK cascade, stage string, acted_by FK users, decision string, remarks text nullable, acted_at timestamp useCurrent, timestamps`.
`osa_form3s`: `id, activity_request_id FK cascade unique, program_name, course, destination_venue, inclusive_dates, number_of_students integer, personnel_in_charge text, moderator_approval_status string default 'pending', moderator_id FK users nullable, moderator_approved_at timestamp nullable, timestamps`.
`osa_form3_compliance_items`: `id, osa_form3_id FK cascade, activity_label, compliance boolean, remarks nullable, timestamps`.
`reference_slips`: `id, activity_request_id FK cascade unique, reference_code string unique, generated_at timestamp useCurrent, claimed_at timestamp nullable, timestamps`.

- [ ] **Step 3: Enums + models**

`ActivityStatus`:
```php
enum ActivityStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case ModeratorEndorsed = 'moderator_endorsed';
    case OsaReviewing = 'osa_reviewing';
    case DocsComplete = 'docs_complete';
    case AwaitingPhysical = 'awaiting_physical';
    case Approved = 'approved';
    case Denied = 'denied';
    case RevisionNeeded = 'revision_needed';
}
```
(`DocumentType`: `ModeratorId='moderator_id', DeanId='dean_id', ParentId='parent_id', StudentId='student_id', ParticipantListFile='participant_list_file', ScheduleFile='schedule_file'`; `ChecklistStatus`, `ApprovalStage`, `Decision` per the Interfaces block above.)

`ActivityRequest` model:
```php
class ActivityRequest extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected function casts(): array
    {
        return ['status' => ActivityStatus::class, 'date_start' => 'date',
                'date_end' => 'date', 'submitted_at' => 'datetime'];
    }
    public function organization() { return $this->belongsTo(Organization::class); }
    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function participants() { return $this->hasMany(Participant::class); }
    public function scheduleItems() { return $this->hasMany(ScheduleItem::class); }
    public function documentUploads() { return $this->hasMany(DocumentUpload::class); }
    public function checklistItems() { return $this->hasMany(ChecklistItem::class); }
    public function approvals() { return $this->hasMany(Approval::class); }
    public function osaForm3() { return $this->hasOne(OsaForm3::class); }
    public function referenceSlip() { return $this->hasOne(ReferenceSlip::class); }
    public function scopeStatus($q, ActivityStatus $s) { return $q->where('status', $s); }
    public function isOffCampus(): bool { return $this->activity_type === 'off_campus'; }
}
```
Child models: `protected $guarded = [];` plus the inverse `belongsTo` and enum casts (`ChecklistItem.status => ChecklistStatus`, `DocumentUpload.document_type => DocumentType`, `Approval.stage/decision`, `OsaForm3.moderator_approval_status` stays a plain string per ERD, `ChecklistItem.is_physical => bool`, `OsaForm3ComplianceItem.compliance => bool`). `ChecklistItem` needs `public function activityRequest()`.

`ActivityRequestFactory`:
```php
public function definition(): array
{
    return [
        'organization_id' => Organization::factory(),
        'submitted_by' => User::factory()->state(['role' => Role::OrgOfficer]),
        'activity_type' => 'in_campus',
        'title' => fake()->sentence(3),
        'nature_of_activity' => 'Seminar',
        'nature_of_engagement' => 'organizer',
        'date_start' => now()->addDays(7)->toDateString(),
        'date_end' => now()->addDays(7)->toDateString(),
        'time_of_activity' => '09:00',
        'venue' => 'AVR 1',
        'purpose' => fake()->paragraph(),
        'status' => ActivityStatus::Submitted,
        'submitted_at' => now(),
    ];
}
public function offCampus(): static { return $this->state(['activity_type' => 'off_campus']); }
```
Also link the factory submitter to the same organization via `afterCreating` or a state — simplest: in `configure()`, `->afterCreating(fn ($r) => $r->submitter->update(['organization_id' => $r->organization_id]));`

- [ ] **Step 4: Run tests, commit**

Run: `php artisan test` — PASS. Also run `php artisan migrate:fresh` against MySQL to prove migrations run on the real driver.
```bash
git add -A && git commit -m "feat: domain schema, enums, and Eloquent models per ERD"
```

---

### Task 5: ChecklistService — seeding & completeness

**Files:**
- Create: `app/Services/ChecklistService.php`
- Test: `tests/Feature/ChecklistServiceTest.php`

**Interfaces:**
- Consumes: `ActivityRequest`, `ChecklistItem`, `ChecklistStatus` (Task 4).
- Produces:
  - `ChecklistService::seedFor(ActivityRequest $request): void` — creates checklist rows per activity type.
  - `ChecklistService::isComplete(ActivityRequest $request): bool` — true when every `is_physical = false` item is `submitted` or `verified`.
  - `ChecklistService::markSubmitted(ActivityRequest $request, string $itemName): void` — used by upload/OSA Form 3 flows.
  - Item-name constants later tasks reference: `ChecklistService::ITEM_PARTICIPANT_LIST`, `ITEM_SCHEDULE`, `ITEM_MODERATOR_ID`, `ITEM_DEAN_ID`, `ITEM_STUDENT_ID`, `ITEM_PARENT_ID`, `ITEM_OSA_FORM_3`.

- [ ] **Step 1: Failing tests**

```php
<?php
use App\Models\ActivityRequest;
use App\Services\ChecklistService;

it('seeds the in-campus checklist', function () {
    $r = ActivityRequest::factory()->create();
    app(ChecklistService::class)->seedFor($r);
    $names = $r->checklistItems()->pluck('item_name');
    expect($names)->toContain('List of Participants', "Parent's Consent (1 copy, wet signature)",
        'Pink Form — FORM A1.1 (wet signatures)')
        ->not->toContain('OSA Form 3 (digital form)');
    expect($r->checklistItems()->where('is_physical', true)->count())->toBe(3);
});

it('seeds the off-campus checklist with bundled Blue Form + Certificate of Compliance', function () {
    $r = ActivityRequest::factory()->offCampus()->create();
    app(ChecklistService::class)->seedFor($r);
    $names = $r->checklistItems()->pluck('item_name');
    expect($names)->toContain('OSA Form 3 (digital form)',
        'Blue Form — FORM A2.2 + Certificate of Compliance (wet signatures, notarized)',
        "Parent's Consent (3 notarized copies, wet signature)")
        ->and($names->filter(fn ($n) => str_contains($n, 'Certificate of Compliance')))->toHaveCount(1);
});

it('is complete when all digital items are submitted even if physical items are pending', function () {
    $r = ActivityRequest::factory()->create();
    $svc = app(ChecklistService::class);
    $svc->seedFor($r);
    expect($svc->isComplete($r))->toBeFalse();
    $r->checklistItems()->where('is_physical', false)->update(['status' => 'submitted']);
    expect($svc->isComplete($r->refresh()))->toBeTrue();
});
```
Run — FAIL.

- [ ] **Step 2: Implement**

```php
<?php
namespace App\Services;

use App\Enums\ChecklistStatus;
use App\Models\ActivityRequest;

class ChecklistService
{
    public const ITEM_PARTICIPANT_LIST = 'List of Participants';
    public const ITEM_SCHEDULE = 'Itinerary / Schedule of Activities';
    public const ITEM_MODERATOR_ID = 'ID Photocopy — Moderator';
    public const ITEM_DEAN_ID = 'ID Photocopy — Dean/Head of Office';
    public const ITEM_STUDENT_ID = 'ID Photocopy — Student';
    public const ITEM_PARENT_ID = 'ID Photocopy — Parent/Guardian';
    public const ITEM_OSA_FORM_3 = 'OSA Form 3 (digital form)';

    /** @return array<string, bool> item_name => is_physical (from FORM A1, docs/*-campus forms/) */
    public static function template(string $activityType): array
    {
        $digital = [
            self::ITEM_PARTICIPANT_LIST => false,
            self::ITEM_SCHEDULE => false,
            self::ITEM_MODERATOR_ID => false,
            self::ITEM_DEAN_ID => false,
            self::ITEM_STUDENT_ID => false,
            self::ITEM_PARENT_ID => false,
        ];
        return $activityType === 'off_campus'
            ? $digital + [
                self::ITEM_OSA_FORM_3 => false,
                'Blue Form — FORM A2.2 + Certificate of Compliance (wet signatures, notarized)' => true,
                "Parent's Consent (3 notarized copies, wet signature)" => true,
                'Medical Certificate (infirmary clearance, if strenuous/out-of-town)' => true,
            ]
            : $digital + [
                'Pink Form — FORM A1.1 (wet signatures)' => true,
                "Parent's Consent (1 copy, wet signature)" => true,
                'Medical Certificate (infirmary clearance, if strenuous)' => true,
            ];
    }

    public function seedFor(ActivityRequest $request): void
    {
        foreach (self::template($request->activity_type) as $name => $physical) {
            $request->checklistItems()->firstOrCreate(
                ['item_name' => $name],
                ['is_physical' => $physical, 'status' => ChecklistStatus::Pending],
            );
        }
    }

    public function isComplete(ActivityRequest $request): bool
    {
        return ! $request->checklistItems()
            ->where('is_physical', false)
            ->where('status', ChecklistStatus::Pending)
            ->exists();
    }

    public function markSubmitted(ActivityRequest $request, string $itemName): void
    {
        $request->checklistItems()
            ->where('item_name', $itemName)
            ->where('status', ChecklistStatus::Pending)
            ->update(['status' => ChecklistStatus::Submitted]);
    }
}
```

- [ ] **Step 3: Run tests, commit**

Run: `php artisan test --filter=ChecklistService` — PASS.
```bash
git add -A && git commit -m "feat: checklist seeding + completeness per activity type"
```

---

### Task 6: Activity request creation (org flow)

**Files:**
- Create: `app/Services/ActivityRequestService.php`, `app/Http/Requests/StoreActivityRequestRequest.php`, `app/Http/Controllers/Org/ActivityRequestController.php`, `app/Policies/ActivityRequestPolicy.php`, `resources/views/org/requests/create.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Org/CreateRequestTest.php`

**Interfaces:**
- Consumes: `ChecklistService::seedFor` (Task 5), models (Task 4), `role` middleware (Task 2).
- Produces:
  - `ActivityRequestService::create(array $validated, \App\Models\User $officer): ActivityRequest` — creates request (status `submitted`, `submitted_at` now), inline `participants[]` and `schedule_items[]` rows, seeds checklist, notifies moderators (notification added Task 9 — call site left as an event hook here).
  - Routes: `GET org/requests/create` → `org.requests.create`, `POST org/requests` → `org.requests.store`, `GET org/requests/{activityRequest}` → `org.requests.show` (view built Task 7).
  - `ActivityRequestPolicy@view`: org officers see own org's requests only; moderators see assigned orgs' requests; OSA roles see all. `@update`: submitter's org + status in (`draft`,`revision_needed`).

- [ ] **Step 1: Failing tests**

```php
<?php
use App\Enums\Role;
use App\Models\{ActivityRequest, Organization, User};

function officer(): User {
    return User::factory()->create(['role' => Role::OrgOfficer, 'organization_id' => Organization::factory()]);
}

it('stores a request, seeds checklist, saves participants and schedule', function () {
    $u = officer();
    $payload = [
        'activity_type' => 'in_campus', 'title' => 'Acquaintance Party',
        'nature_of_activity' => 'Social', 'nature_of_engagement' => 'organizer',
        'date_start' => now()->addDays(5)->toDateString(), 'date_end' => now()->addDays(5)->toDateString(),
        'time_of_activity' => '13:00', 'venue' => 'Carlos Dominguez Hall', 'purpose' => 'Welcome freshmen.',
        'participants' => [['full_name' => 'Juan Dela Cruz', 'year_course' => 'BSCS 2']],
        'schedule_items' => [['time_slot' => '13:00-14:00', 'description' => 'Opening']],
    ];
    $this->actingAs($u)->post(route('org.requests.store'), $payload)->assertRedirect();
    $r = ActivityRequest::sole();
    expect($r->status->value)->toBe('submitted')
        ->and($r->organization_id)->toBe($u->organization_id)
        ->and($r->participants)->toHaveCount(1)
        ->and($r->scheduleItems)->toHaveCount(1)
        ->and($r->checklistItems()->count())->toBeGreaterThan(0);
});

it('rejects activities starting less than 3 days from now', function () {
    $u = officer();
    $this->actingAs($u)->post(route('org.requests.store'), [
        'activity_type' => 'in_campus', 'title' => 'Late', 'nature_of_activity' => 'Social',
        'nature_of_engagement' => 'organizer', 'date_start' => now()->addDay()->toDateString(),
        'date_end' => now()->addDay()->toDateString(), 'time_of_activity' => '13:00',
        'venue' => 'AVR', 'purpose' => 'x',
    ])->assertSessionHasErrors('date_start');
});

it('blocks non-org roles from creating', function () {
    $mod = User::factory()->create(['role' => Role::Moderator]);
    $this->actingAs($mod)->get(route('org.requests.create'))->assertForbidden();
});
```
Run — FAIL.

- [ ] **Step 2: Form request (3-day rule lives here)**

```php
<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'activity_type' => ['required', 'in:in_campus,off_campus'],
            'title' => ['required', 'string', 'max:255'],
            'nature_of_activity' => ['required', 'string', 'max:255'],
            'nature_of_engagement' => ['required', 'in:organizer,partner,participant'],
            'main_organizer' => ['nullable', 'string', 'max:255', 'required_unless:nature_of_engagement,organizer'],
            'date_start' => ['required', 'date', 'after_or_equal:'.now()->addDays(3)->toDateString()],
            'date_end' => ['required', 'date', 'after_or_equal:date_start'],
            'time_of_activity' => ['required', 'date_format:H:i'],
            'venue' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string'],
            'participants' => ['array'],
            'participants.*.full_name' => ['required', 'string', 'max:255'],
            'participants.*.year_course' => ['nullable', 'string', 'max:255'],
            'participants.*.contact_info' => ['nullable', 'string', 'max:255'],
            'schedule_items' => ['array'],
            'schedule_items.*.time_slot' => ['required', 'string', 'max:255'],
            'schedule_items.*.description' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['date_start.after_or_equal' => 'Requests must be submitted at least 3 days before the activity.'];
    }
}
```

- [ ] **Step 3: Service, controller, policy, routes**

`ActivityRequestService`:
```php
<?php
namespace App\Services;

use App\Enums\ActivityStatus;
use App\Models\{ActivityRequest, User};
use Illuminate\Support\Facades\DB;

class ActivityRequestService
{
    public function __construct(private ChecklistService $checklist) {}

    public function create(array $validated, User $officer): ActivityRequest
    {
        return DB::transaction(function () use ($validated, $officer) {
            $request = ActivityRequest::create([
                ...collect($validated)->except(['participants', 'schedule_items'])->all(),
                'organization_id' => $officer->organization_id,
                'submitted_by' => $officer->id,
                'status' => ActivityStatus::Submitted,
                'submitted_at' => now(),
            ]);
            $request->participants()->createMany($validated['participants'] ?? []);
            $request->scheduleItems()->createMany($validated['schedule_items'] ?? []);
            $this->checklist->seedFor($request);
            // Task 9 appends: notify assigned moderators (NewRequestAwaitingEndorsement)
            return $request;
        });
    }
}
```
`Org/ActivityRequestController`:
```php
public function create() { return view('org.requests.create'); }

public function store(StoreActivityRequestRequest $req, ActivityRequestService $service)
{
    $request = $service->create($req->validated(), $req->user());
    return redirect()->route('org.requests.show', $request)
        ->with('status', 'Request submitted and routed to your moderator.');
}

public function show(ActivityRequest $activityRequest)
{
    $this->authorize('view', $activityRequest);
    $activityRequest->load(['checklistItems', 'participants', 'scheduleItems', 'documentUploads', 'approvals.actor', 'osaForm3', 'referenceSlip']);
    return view('org.requests.show', ['request' => $activityRequest]);
}
```
(`approvals.actor` requires `Approval::actor()` BelongsTo `users.acted_by` — add it in the Approval model now.)
`ActivityRequestPolicy`:
```php
public function view(User $user, ActivityRequest $r): bool
{
    return match ($user->role) {
        Role::OrgOfficer => $user->organization_id === $r->organization_id,
        Role::Moderator => $user->moderatedOrganizations()->whereKey($r->organization_id)->exists(),
        Role::OsaAdmin, Role::OsaDirector => true,
    };
}
public function update(User $user, ActivityRequest $r): bool
{
    return $user->role === Role::OrgOfficer
        && $user->organization_id === $r->organization_id
        && in_array($r->status, [ActivityStatus::Draft, ActivityStatus::RevisionNeeded], true);
}
```
Register in `AppServiceProvider::boot()`: `Gate::policy(ActivityRequest::class, ActivityRequestPolicy::class);`
Routes (inside the auth group):
```php
Route::middleware('role:org_officer')->prefix('org')->name('org.')->group(function () {
    Route::get('requests/create', [ActivityRequestController::class, 'create'])->name('requests.create');
    Route::post('requests', [ActivityRequestController::class, 'store'])->name('requests.store');
    Route::get('requests/{activityRequest}', [ActivityRequestController::class, 'show'])->name('requests.show');
});
```

- [ ] **Step 4: Create form view**

`org/requests/create.blade.php` — white card on `paper-muted`, Alpine for repeatable participant/schedule rows and off-campus toggle:
```blade
<x-app-layout>
    <div class="max-w-3xl mx-auto py-8 px-4" x-data="{ type: 'in_campus', participants: [{}], schedule: [{}] }">
        <x-hero-panel title="New activity request" />
        <form method="POST" action="{{ route('org.requests.store') }}" class="bg-paper border border-slate-200 rounded-xl p-6 mt-6 space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Activity type</label>
                <select name="activity_type" x-model="type" class="w-full rounded-lg border-slate-200">
                    <option value="in_campus">In-campus</option>
                    <option value="off_campus">Off-campus</option>
                </select>
            </div>
            {{-- title, nature_of_activity, nature_of_engagement (select), main_organizer,
                 date_start, date_end, time_of_activity, venue, purpose (textarea):
                 same label+input pattern, each with @error display:
                 @error('title')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror --}}
            <fieldset>
                <legend class="text-sm font-medium">Participants</legend>
                <template x-for="(p, i) in participants" :key="i">
                    <div class="grid grid-cols-3 gap-2 mt-2">
                        <input :name="`participants[${i}][full_name]`" placeholder="Full name" class="rounded-lg border-slate-200">
                        <input :name="`participants[${i}][year_course]`" placeholder="Year & course" class="rounded-lg border-slate-200">
                        <input :name="`participants[${i}][contact_info]`" placeholder="Contact" class="rounded-lg border-slate-200">
                    </div>
                </template>
                <button type="button" @click="participants.push({})" class="mt-2 text-sm text-navy-700">+ Add participant</button>
            </fieldset>
            {{-- schedule_items fieldset: same x-for pattern with time_slot + description --}}
            <p class="text-xs text-gray-500">Requests must be filed at least 3 days before the activity date.</p>
            <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">Submit request</button>
        </form>
    </div>
</x-app-layout>
```
Fill in the commented field groups literally (each is `<div><label>…</label><input name="…" value="{{ old('…') }}" class="w-full rounded-lg border-slate-200">@error…</div>`). Add `<x-heroicon-o-exclamation-triangle>`-prefixed banner listing `$errors->all()` at the top when validation fails.

- [ ] **Step 5: Run tests, verify in browser, commit**

Run: `php artisan test` — PASS. Then `php artisan serve`, log in as a seeded officer (quick tinker user), submit a request end-to-end.
```bash
git add -A && git commit -m "feat: digital activity request submission with 3-day rule and checklist seeding"
```

---

### Task 7: Org dashboard & request detail page

**Files:**
- Create: `resources/views/org/requests/show.blade.php`
- Modify: `resources/views/org/dashboard.blade.php` (replace placeholder), `routes/web.php` (dashboard controller method), `app/Http/Controllers/Org/ActivityRequestController.php` (`index`)
- Test: `tests/Feature/Org/DashboardTest.php`

**Interfaces:**
- Consumes: components (Task 3), policy + show route (Task 6).
- Produces: `org.dashboard` lists own-org requests with `<x-status-pill>`; `org.requests.show` renders `<x-timeline :current="$request->status->value">`, checklist as `<x-file-card>` grid (physical = amber, no upload slot; digital cards get upload slots in Task 8), participants/schedule tables, approvals log.

- [ ] **Step 1: Failing tests**

```php
it('shows only own-org requests on the dashboard', function () {
    $u = officer();
    $mine = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);
    $other = ActivityRequest::factory()->create();
    $this->actingAs($u)->get(route('org.dashboard'))
        ->assertOk()->assertSee($mine->title)->assertDontSee($other->title);
});

it('blocks viewing another org\'s request', function () {
    $u = officer();
    $other = ActivityRequest::factory()->create();
    $this->actingAs($u)->get(route('org.requests.show', $other))->assertForbidden();
});
```
(Reuse the `officer()` helper by moving it to `tests/Pest.php`.)
Run — FAIL (dashboard is a static placeholder).

- [ ] **Step 2: Implement**

Replace the placeholder route with `Route::get('/org', [ActivityRequestController::class, 'index'])->…->name('org.dashboard');`
```php
public function index(Request $request)
{
    $requests = ActivityRequest::where('organization_id', $request->user()->organization_id)
        ->latest('submitted_at')->paginate(10);
    return view('org.dashboard', compact('requests'));
}
```
`org/dashboard.blade.php`: hero panel with org name + `<x-eyebrow-badge>AY {{ now()->year }}–{{ now()->year + 1 }}</x-eyebrow-badge>`, "New request" primary button, then a white-card table: Title / Type / Activity date / `<x-status-pill>` / View link. Empty state uses `font-display uppercase` headline per Design System §2.

`org/requests/show.blade.php`:
```blade
<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4 space-y-6">
        <x-hero-panel :title="$request->title" :reference="$request->referenceSlip?->reference_code" />
        <div class="bg-paper border border-slate-200 rounded-xl p-5">
            <x-timeline :current="$request->status->value" />
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            @foreach($request->checklistItems as $item)
                <x-file-card :label="$item->item_name" :physical="$item->is_physical" :status="$item->status->value">
                    @if($item->is_physical)
                        <p class="text-xs text-amber-700">Submit physically at the OSA office.</p>
                    @endif {{-- Task 8 adds upload forms here for digital items --}}
                </x-file-card>
            @endforeach
        </div>
        {{-- participants table, schedule table, approvals log:
             approvals log = list of $request->approvals: stage label, actor name, decision pill, remarks, acted_at --}}
    </div>
</x-app-layout>
```

- [ ] **Step 3: Run tests, commit**

Run: `php artisan test` — PASS.
```bash
git add -A && git commit -m "feat: org dashboard and request detail with timeline + checklist cards"
```

---

### Task 8: Document uploads

**Files:**
- Create: `app/Http/Requests/UploadDocumentRequest.php`, `app/Http/Controllers/Org/DocumentUploadController.php`
- Modify: `resources/views/org/requests/show.blade.php` (upload forms in digital file-cards), `routes/web.php`, `app/Services/ChecklistService.php` (add `itemNameFor(DocumentType)` map)
- Test: `tests/Feature/Org/DocumentUploadTest.php`

**Interfaces:**
- Consumes: `ChecklistService::markSubmitted`, `DocumentType` enum, policy `update`… note uploads are allowed while status is submitted→osa_reviewing too, so add `ActivityRequestPolicy@upload`: org officer of the request's org AND status NOT in (`approved`,`denied`,`awaiting_physical`).
- Produces: `POST org/requests/{activityRequest}/documents` → `org.documents.store` storing to `public` disk path `uploads/{request_id}/…`; `ChecklistService::itemNameFor(DocumentType $type): string` mapping each `DocumentType` case to its checklist item constant (`ParticipantListFile → ITEM_PARTICIPANT_LIST`, `ScheduleFile → ITEM_SCHEDULE`, `ModeratorId → ITEM_MODERATOR_ID`, `DeanId → ITEM_DEAN_ID`, `StudentId → ITEM_STUDENT_ID`, `ParentId → ITEM_PARENT_ID`).

- [ ] **Step 1: Failing tests**

```php
<?php
use App\Models\ActivityRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('uploads a document and marks the checklist item submitted', function () {
    Storage::fake('public');
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);
    app(\App\Services\ChecklistService::class)->seedFor($r);

    $this->actingAs($u)->post(route('org.documents.store', $r), [
        'document_type' => 'participant_list_file',
        'file' => UploadedFile::fake()->create('list.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    expect($r->documentUploads()->count())->toBe(1)
        ->and($r->checklistItems()->where('item_name', 'List of Participants')->sole()->status->value)->toBe('submitted');
    Storage::disk('public')->assertExists($r->documentUploads()->sole()->file_path);
});

it('rejects physical-only document types', function () {
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);
    $this->actingAs($u)->post(route('org.documents.store', $r), [
        'document_type' => 'parents_consent', // not in the enum
        'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
    ])->assertSessionHasErrors('document_type');
});

it('rejects oversized files', function () {
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);
    $this->actingAs($u)->post(route('org.documents.store', $r), [
        'document_type' => 'student_id',
        'file' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
    ])->assertSessionHasErrors('file');
});
```
Run — FAIL.

- [ ] **Step 2: Implement**

`UploadDocumentRequest`:
```php
public function rules(): array
{
    return [
        'document_type' => ['required', new \Illuminate\Validation\Rules\Enum(\App\Enums\DocumentType::class)],
        'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
    ];
}
```
`DocumentUploadController@store`:
```php
public function store(UploadDocumentRequest $req, ActivityRequest $activityRequest, ChecklistService $checklist)
{
    $this->authorize('upload', $activityRequest);
    $type = DocumentType::from($req->validated('document_type'));
    $path = $req->file('file')->store("uploads/{$activityRequest->id}", 'public');
    $activityRequest->documentUploads()->create([
        'document_type' => $type,
        'file_path' => $path,
        'uploaded_by' => $req->user()->id,
        'uploaded_at' => now(),
    ]);
    $checklist->markSubmitted($activityRequest, $checklist->itemNameFor($type));
    return back()->with('status', 'Document uploaded.');
}
```
`ChecklistService::itemNameFor`:
```php
public function itemNameFor(\App\Enums\DocumentType $type): string
{
    return match ($type) {
        \App\Enums\DocumentType::ParticipantListFile => self::ITEM_PARTICIPANT_LIST,
        \App\Enums\DocumentType::ScheduleFile => self::ITEM_SCHEDULE,
        \App\Enums\DocumentType::ModeratorId => self::ITEM_MODERATOR_ID,
        \App\Enums\DocumentType::DeanId => self::ITEM_DEAN_ID,
        \App\Enums\DocumentType::StudentId => self::ITEM_STUDENT_ID,
        \App\Enums\DocumentType::ParentId => self::ITEM_PARENT_ID,
    };
}
```
Route: `Route::post('requests/{activityRequest}/documents', [DocumentUploadController::class, 'store'])->name('documents.store');` in the org group.

In `org/requests/show.blade.php`, inside each digital file-card slot render an upload form (compute type per item — pass a `['item_name' => DocumentType]` map via the controller, i.e., invert `itemNameFor` over `DocumentType::cases()` and hand it to the view as `$uploadTypes`):
```blade
@if(! $item->is_physical && isset($uploadTypes[$item->item_name]) && $item->status->value === 'pending')
    <form method="POST" action="{{ route('org.documents.store', $request) }}" enctype="multipart/form-data" class="flex gap-2 items-center">
        @csrf
        <input type="hidden" name="document_type" value="{{ $uploadTypes[$item->item_name]->value }}">
        <input type="file" name="file" required class="text-xs">
        <button class="bg-navy-900 text-white rounded-lg px-3 py-1.5 text-xs font-medium">Upload</button>
    </form>
@elseif(! $item->is_physical)
    @php $upload = $request->documentUploads->firstWhere('document_type', $uploadTypes[$item->item_name] ?? null); @endphp
    @if($upload)<a href="{{ Storage::url($upload->file_path) }}" class="text-xs text-navy-700 underline">View file</a>@endif
@endif
```
(The OSA Form 3 checklist card links to the Task 10 form instead of an upload input.)

- [ ] **Step 3: Run tests, commit**

Run: `php artisan test` — PASS.
```bash
git add -A && git commit -m "feat: document upload center with checklist auto-marking"
```

---

### Task 9: Moderator endorsement + core notifications

**Files:**
- Create: `app/Services/EndorsementService.php`, `app/Http/Controllers/Moderator/EndorsementController.php`, `app/Http/Requests/DecisionRequest.php`, `app/Notifications/NewRequestAwaitingEndorsement.php`, `app/Notifications/StatusChanged.php`, `resources/views/moderator/queue.blade.php` (replace placeholder), `resources/views/moderator/show.blade.php`, `database/migrations/*_create_notifications_table.php` (`php artisan make:notifications-table`)
- Modify: `app/Services/ActivityRequestService.php` (fire moderator notification), `routes/web.php`
- Test: `tests/Feature/Moderator/EndorsementTest.php`

**Interfaces:**
- Consumes: `Approval` model, `ApprovalStage`/`Decision`/`ActivityStatus` enums, org pivot (Task 2).
- Produces:
  - `EndorsementService::decide(ActivityRequest $request, User $actor, ApprovalStage $stage, Decision $decision, ?string $remarks = null): void` — records the `APPROVALS` row, transitions status (`moderator_endorsement`+`approved` → `moderator_endorsed`; any stage +`rejected` → `denied`; +`revision_requested` → `revision_needed`), notifies the org's officers via `StatusChanged`. Task 11/12 reuse this same method for `osa_review` and `osa_director_notation` stages (`osa_review`+`approved` → no status change — OSA review approval happens implicitly via checklist verification; `osa_director_notation`+`approved` → `awaiting_physical`).
  - Routes: `GET /moderator/queue` (`moderator.queue`, replaces placeholder), `GET /moderator/requests/{activityRequest}` (`moderator.requests.show`), `POST /moderator/requests/{activityRequest}/decision` (`moderator.requests.decide`).
  - `DecisionRequest` rules: `decision => required|in:approved,rejected,revision_requested`, `remarks => nullable|string|required_unless:decision,approved`.
  - Notifications use `via(['database'])`, payload `['activity_request_id', 'title', 'message']`.

- [ ] **Step 1: Failing tests**

```php
<?php
use App\Enums\{ActivityStatus, Role};
use App\Models\{ActivityRequest, User};
use App\Notifications\{NewRequestAwaitingEndorsement, StatusChanged};
use Illuminate\Support\Facades\Notification;

function moderatorFor(ActivityRequest $r): User {
    $m = User::factory()->create(['role' => Role::Moderator]);
    $m->moderatedOrganizations()->attach($r->organization_id);
    return $m;
}

it('endorsing records an approval and advances status', function () {
    $r = ActivityRequest::factory()->create();
    $m = moderatorFor($r);
    $this->actingAs($m)->post(route('moderator.requests.decide', $r), ['decision' => 'approved'])
        ->assertRedirect();
    expect($r->refresh()->status)->toBe(ActivityStatus::ModeratorEndorsed)
        ->and($r->approvals()->sole()->stage->value)->toBe('moderator_endorsement');
});

it('revision request sets revision_needed and notifies the org officer', function () {
    Notification::fake();
    $r = ActivityRequest::factory()->create();
    $m = moderatorFor($r);
    $this->actingAs($m)->post(route('moderator.requests.decide', $r),
        ['decision' => 'revision_requested', 'remarks' => 'Fix venue']);
    expect($r->refresh()->status)->toBe(ActivityStatus::RevisionNeeded);
    Notification::assertSentTo($r->submitter, StatusChanged::class);
});

it('unassigned moderators cannot decide', function () {
    $r = ActivityRequest::factory()->create();
    $stranger = User::factory()->create(['role' => Role::Moderator]);
    $this->actingAs($stranger)->post(route('moderator.requests.decide', $r), ['decision' => 'approved'])
        ->assertForbidden();
});

it('submitting a request notifies assigned moderators', function () {
    Notification::fake();
    $u = officer();
    $m = User::factory()->create(['role' => Role::Moderator]);
    $m->moderatedOrganizations()->attach($u->organization_id);
    $this->actingAs($u)->post(route('org.requests.store'), validRequestPayload()); // helper in Pest.php from Task 6 payload
    Notification::assertSentTo($m, NewRequestAwaitingEndorsement::class);
});
```
Run — FAIL.

- [ ] **Step 2: Implement service + notifications**

`EndorsementService`:
```php
<?php
namespace App\Services;

use App\Enums\{ActivityStatus, ApprovalStage, Decision};
use App\Models\{ActivityRequest, User};
use App\Notifications\StatusChanged;
use Illuminate\Support\Facades\Notification;

class EndorsementService
{
    public function decide(ActivityRequest $request, User $actor, ApprovalStage $stage, Decision $decision, ?string $remarks = null): void
    {
        $request->approvals()->create([
            'stage' => $stage, 'acted_by' => $actor->id,
            'decision' => $decision, 'remarks' => $remarks, 'acted_at' => now(),
        ]);

        $newStatus = match (true) {
            $decision === Decision::Rejected => ActivityStatus::Denied,
            $decision === Decision::RevisionRequested => ActivityStatus::RevisionNeeded,
            $stage === ApprovalStage::ModeratorEndorsement => ActivityStatus::ModeratorEndorsed,
            $stage === ApprovalStage::OsaDirectorNotation => ActivityStatus::AwaitingPhysical,
            default => null, // osa_review approval: status driven by checklist completion (Task 11)
        };
        if ($newStatus) {
            $request->update(['status' => $newStatus]);
        }
        Notification::send($request->organization->officers,
            new StatusChanged($request, $remarks));
    }
}
```
`StatusChanged`:
```php
class StatusChanged extends Notification
{
    public function __construct(public ActivityRequest $request, public ?string $remarks = null) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array
    {
        return [
            'activity_request_id' => $this->request->id,
            'title' => $this->request->title,
            'message' => 'Status: '.str_replace('_', ' ', $this->request->status->value)
                .($this->remarks ? " — {$this->remarks}" : ''),
        ];
    }
}
```
`NewRequestAwaitingEndorsement` mirrors it (`message` = `'New request awaiting your endorsement.'`). In `ActivityRequestService::create`, after seeding the checklist:
```php
Notification::send($request->organization->moderators, new NewRequestAwaitingEndorsement($request));
```
Run `php artisan make:notifications-table && php artisan migrate`.

- [ ] **Step 3: Controller, routes, views**

`EndorsementController`:
```php
public function index(Request $request)
{
    $requests = ActivityRequest::whereIn('organization_id', $request->user()->moderatedOrganizations()->pluck('organizations.id'))
        ->status(ActivityStatus::Submitted)->with('organization')->latest('submitted_at')->paginate(10);
    return view('moderator.queue', compact('requests'));
}

public function show(ActivityRequest $activityRequest)
{
    $this->authorize('view', $activityRequest);
    $activityRequest->load(['organization', 'participants', 'scheduleItems', 'checklistItems', 'documentUploads']);
    return view('moderator.show', ['request' => $activityRequest]);
}

public function decide(DecisionRequest $req, ActivityRequest $activityRequest, EndorsementService $service)
{
    abort_unless($req->user()->moderatedOrganizations()->whereKey($activityRequest->organization_id)->exists(), 403);
    abort_unless($activityRequest->status === ActivityStatus::Submitted, 422);
    $service->decide($activityRequest, $req->user(), ApprovalStage::ModeratorEndorsement,
        Decision::from($req->validated('decision')), $req->validated('remarks'));
    return redirect()->route('moderator.queue')->with('status', 'Decision recorded.');
}
```
Routes in a `role:moderator` prefix group. `moderator/queue.blade.php`: table of pending requests (org, title, type, activity date, submitted date, Review link). `moderator/show.blade.php`: request summary (reuse the structure of the org show page read-only) + decision form:
```blade
<form method="POST" action="{{ route('moderator.requests.decide', $request) }}" class="bg-paper border border-slate-200 rounded-xl p-5 space-y-3">
    @csrf
    <label class="block text-sm font-medium">Decision</label>
    <select name="decision" class="rounded-lg border-slate-200">
        <option value="approved">Endorse</option>
        <option value="revision_requested">Request revisions</option>
        <option value="rejected">Reject</option>
    </select>
    <textarea name="remarks" placeholder="Remarks (required unless endorsing)" class="w-full rounded-lg border-slate-200"></textarea>
    <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">Submit decision</button>
</form>
```

- [ ] **Step 4: Run tests, commit**

Run: `php artisan test` — PASS.
```bash
git add -A && git commit -m "feat: moderator endorsement flow with database notifications"
```

---

### Task 10: OSA Form 3 (off-campus)

**Files:**
- Create: `app/Http/Requests/StoreOsaForm3Request.php`, `app/Http/Controllers/Org/OsaForm3Controller.php`, `app/Http/Controllers/Moderator/OsaForm3ApprovalController.php`, `app/Policies/OsaForm3Policy.php`, `resources/views/org/osa-form-3.blade.php`, `resources/views/moderator/osa-form-3.blade.php`
- Modify: `routes/web.php`, `AppServiceProvider` (register policy)
- Test: `tests/Feature/OsaForm3Test.php`

**Interfaces:**
- Consumes: `OsaForm3`/`OsaForm3ComplianceItem` models, `ChecklistService::markSubmitted` + `ITEM_OSA_FORM_3`.
- Produces:
  - The 11 fixed compliance labels as `OsaForm3Controller::COMPLIANCE_LABELS` public const (copied verbatim from `docs/out-campus forms/OSA FORM 3.md`): `'Curriculum Requirements', 'Destination', 'Handbook or Manual', 'Students — Consents of the Parents/Guardians; Medical Clearance of the students', 'Person In Charge/Moderator', 'First Aid Kit', 'Fees/Fund', 'Insurance', 'Mobility of Students (Vehicles)', 'LGUs/NGOs', 'Activities — Orientation, Consultation, Announcement, Briefing, Learning Journals, Emergency Preparedness Plan'`.
  - Routes: `GET/POST org/requests/{activityRequest}/osa-form-3` (`org.osa-form-3.create/store`), `GET moderator/osa-form-3/{osaForm3}` + `POST …/decision` (`moderator.osa-form-3.show/decide`).
  - Store creates `OsaForm3` + 11 compliance rows, marks the `OSA Form 3 (digital form)` checklist item submitted, notifies moderators. Moderator approval sets `moderator_approval_status`, `moderator_id`, `moderator_approved_at`. Only for off-campus requests (`isOffCampus()` guard, 404 otherwise).

- [ ] **Step 1: Failing tests**

```php
it('org files OSA Form 3 for an off-campus request', function () {
    $u = officer();
    $r = ActivityRequest::factory()->offCampus()->create(['organization_id' => $u->organization_id]);
    app(ChecklistService::class)->seedFor($r);

    $payload = [
        'program_name' => 'Outreach', 'course' => 'BSCS', 'destination_venue' => 'Vitali',
        'inclusive_dates' => 'Sept 12–13, 2026', 'number_of_students' => 40,
        'personnel_in_charge' => 'Ms. Reyes',
        'compliance' => collect(range(0, 10))->map(fn () => ['compliance' => '1', 'remarks' => ''])->all(),
    ];
    $this->actingAs($u)->post(route('org.osa-form-3.store', $r), $payload)->assertRedirect();
    expect($r->osaForm3->complianceItems)->toHaveCount(11)
        ->and($r->checklistItems()->where('item_name', ChecklistService::ITEM_OSA_FORM_3)->sole()->status->value)->toBe('submitted');
});

it('rejects OSA Form 3 on in-campus requests', function () {
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);
    $this->actingAs($u)->get(route('org.osa-form-3.create', $r))->assertNotFound();
});

it('moderator approval stamps the form', function () {
    $r = ActivityRequest::factory()->offCampus()->create();
    $form = $r->osaForm3()->create(['program_name' => 'X', 'course' => 'Y', 'destination_venue' => 'Z',
        'inclusive_dates' => 'dates', 'number_of_students' => 10, 'personnel_in_charge' => 'P']);
    $m = moderatorFor($r);
    $this->actingAs($m)->post(route('moderator.osa-form-3.decide', $form), ['decision' => 'approved']);
    $form->refresh();
    expect($form->moderator_approval_status)->toBe('approved')
        ->and($form->moderator_id)->toBe($m->id)
        ->and($form->moderator_approved_at)->not->toBeNull();
});
```
Run — FAIL.

- [ ] **Step 2: Implement**

`StoreOsaForm3Request` rules:
```php
return [
    'program_name' => ['required', 'string', 'max:255'],
    'course' => ['required', 'string', 'max:255'],
    'destination_venue' => ['required', 'string', 'max:255'],
    'inclusive_dates' => ['required', 'string', 'max:255'],
    'number_of_students' => ['required', 'integer', 'min:1'],
    'personnel_in_charge' => ['required', 'string'],
    'compliance' => ['required', 'array', 'size:11'],
    'compliance.*.compliance' => ['required', 'boolean'],
    'compliance.*.remarks' => ['nullable', 'string', 'max:255'],
];
```
`Org/OsaForm3Controller`:
```php
public const COMPLIANCE_LABELS = [ /* the 11 labels from the Interfaces block, verbatim */ ];

public function create(ActivityRequest $activityRequest)
{
    $this->authorize('upload', $activityRequest);
    abort_unless($activityRequest->isOffCampus(), 404);
    return view('org.osa-form-3', ['request' => $activityRequest, 'labels' => self::COMPLIANCE_LABELS,
        'form' => $activityRequest->osaForm3]);
}

public function store(StoreOsaForm3Request $req, ActivityRequest $activityRequest, ChecklistService $checklist)
{
    $this->authorize('upload', $activityRequest);
    abort_unless($activityRequest->isOffCampus(), 404);
    $form = $activityRequest->osaForm3()->updateOrCreate([], $req->safe()->except('compliance'));
    $form->complianceItems()->delete();
    foreach ($req->validated('compliance') as $i => $row) {
        $form->complianceItems()->create([
            'activity_label' => self::COMPLIANCE_LABELS[$i],
            'compliance' => $row['compliance'], 'remarks' => $row['remarks'] ?? null,
        ]);
    }
    $checklist->markSubmitted($activityRequest, ChecklistService::ITEM_OSA_FORM_3);
    Notification::send($activityRequest->organization->moderators, new NewRequestAwaitingEndorsement($activityRequest));
    return redirect()->route('org.requests.show', $activityRequest)->with('status', 'OSA Form 3 submitted to your moderator.');
}
```
`Moderator/OsaForm3ApprovalController@decide` (route-model-binds `OsaForm3 $osaForm3`): guard assignment via the parent request's org, validate `decision => in:approved,rejected`, then:
```php
$osaForm3->update([
    'moderator_approval_status' => $req->validated('decision'),
    'moderator_id' => $req->user()->id,
    'moderator_approved_at' => now(),
]);
```
Views: `org/osa-form-3.blade.php` renders Basic Information inputs + an 11-row table (`label | Yes/No radio pair named compliance[i][compliance] | remarks input`); `moderator/osa-form-3.blade.php` renders the same read-only + Approve/Reject buttons. Add a "Fill out OSA Form 3" link in the OSA Form 3 file-card slot on `org/requests/show.blade.php`, and a moderator queue section listing forms with `moderator_approval_status = pending` for their orgs (`OsaForm3ApprovalController@index`, route `moderator.osa-form-3.index`, linked from nav).
`OsaForm3Policy` mirrors `ActivityRequestPolicy@view` on `$osaForm3->activityRequest`.

- [ ] **Step 3: Run tests, commit**

Run: `php artisan test` — PASS.
```bash
git add -A && git commit -m "feat: digital OSA Form 3 with single moderator recommending approval"
```

---

### Task 11: OSA Admin review queue & checklist verification

**Files:**
- Create: `app/Http/Controllers/OsaAdmin/ReviewQueueController.php`, `app/Http/Controllers/OsaAdmin/ChecklistController.php`, `app/Notifications/PacketReadyForPhysicalStage.php`, `app/Notifications/DocumentsMissing.php`, `resources/views/osa-admin/queue.blade.php` (replace placeholder), `resources/views/osa-admin/show.blade.php`
- Modify: `routes/web.php`, `app/Services/ChecklistService.php` (`verify()` + auto-advance)
- Test: `tests/Feature/OsaAdmin/ReviewTest.php`

**Interfaces:**
- Consumes: `EndorsementService::decide` (Task 9), `ChecklistService::isComplete` (Task 5).
- Produces:
  - Routes (prefix `osa-admin`, `role:osa_admin`): `GET queue` (filterable: `?status=&activity_type=&organization_id=&date_from=&date_to=&complete=`), `GET requests/{activityRequest}` (`osa-admin.requests.show`), `POST requests/{activityRequest}/start-review` (`osa-admin.requests.start-review`: `moderator_endorsed → osa_reviewing`), `POST requests/{activityRequest}/decision` (`osa-admin.requests.decide`, stage `osa_review`, for revision/denial), `PATCH checklist/{checklistItem}` (`osa-admin.checklist.update`).
  - `ChecklistService::verify(ChecklistItem $item, ?string $notes = null): void` — sets `verified` (any item, incl. physical when sighted at the counter); after verifying, if `isComplete()` and status is `osa_reviewing`, auto-advance to `docs_complete` + notify OSA admins (`PacketReadyForPhysicalStage`) and org officers (`StatusChanged`).
  - `POST requests/{activityRequest}/nudge` (`osa-admin.requests.nudge`) sends `DocumentsMissing` to org officers listing pending digital items.
  - Late-submission flag: queue rows where `submitted_at > date_start - 3 days` show an amber "Filed late" `<x-eyebrow-badge>`-style warning.

- [ ] **Step 1: Failing tests**

```php
function admin(): User { return User::factory()->create(['role' => Role::OsaAdmin]); }

it('filters the queue by status and type', function () {
    $a = admin();
    $endorsed = ActivityRequest::factory()->create(['status' => ActivityStatus::ModeratorEndorsed]);
    $submitted = ActivityRequest::factory()->create(['status' => ActivityStatus::Submitted]);
    $this->actingAs($a)->get(route('osa-admin.queue', ['status' => 'moderator_endorsed']))
        ->assertOk()->assertSee($endorsed->title)->assertDontSee($submitted->title);
});

it('verifying the last digital item auto-advances to docs_complete and notifies', function () {
    Notification::fake();
    $a = admin();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::OsaReviewing]);
    app(ChecklistService::class)->seedFor($r);
    $digital = $r->checklistItems()->where('is_physical', false)->get();
    $digital->each->update(['status' => 'submitted']);

    foreach ($digital as $item) {
        $this->actingAs($a)->patch(route('osa-admin.checklist.update', $item), ['status' => 'verified']);
    }
    expect($r->refresh()->status)->toBe(ActivityStatus::DocsComplete);
    Notification::assertSentTo($a, \App\Notifications\PacketReadyForPhysicalStage::class);
});

it('admin can request revisions', function () {
    $a = admin();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::OsaReviewing]);
    $this->actingAs($a)->post(route('osa-admin.requests.decide', $r),
        ['decision' => 'revision_requested', 'remarks' => 'Missing venue details']);
    expect($r->refresh()->status)->toBe(ActivityStatus::RevisionNeeded);
});
```
Run — FAIL.

- [ ] **Step 2: Implement**

`ChecklistService` additions:
```php
public function verify(\App\Models\ChecklistItem $item, ?string $notes = null): void
{
    $item->update(['status' => ChecklistStatus::Verified, 'notes' => $notes ?? $item->notes]);
    $request = $item->activityRequest;
    if ($request->status === \App\Enums\ActivityStatus::OsaReviewing && $this->isComplete($request)) {
        $request->update(['status' => \App\Enums\ActivityStatus::DocsComplete]);
        Notification::send(\App\Models\User::where('role', \App\Enums\Role::OsaAdmin)->get(),
            new \App\Notifications\PacketReadyForPhysicalStage($request));
        Notification::send($request->organization->officers, new \App\Notifications\StatusChanged($request));
    }
}
```
`ReviewQueueController@index`:
```php
public function index(Request $req)
{
    $requests = ActivityRequest::query()
        ->with(['organization', 'checklistItems'])
        ->when($req->status, fn ($q, $s) => $q->where('status', $s))
        ->when($req->activity_type, fn ($q, $t) => $q->where('activity_type', $t))
        ->when($req->organization_id, fn ($q, $o) => $q->where('organization_id', $o))
        ->when($req->date_from, fn ($q, $d) => $q->whereDate('date_start', '>=', $d))
        ->when($req->date_to, fn ($q, $d) => $q->whereDate('date_start', '<=', $d))
        ->whereNot('status', ActivityStatus::Draft)
        ->latest('submitted_at')->paginate(15)->withQueryString();
    return view('osa-admin.queue', ['requests' => $requests, 'organizations' => Organization::orderBy('name')->get()]);
}
```
`@startReview`: guard `status === ModeratorEndorsed`, set `OsaReviewing`, notify officers via `StatusChanged`. `@decide`: guard status in (`OsaReviewing`, `ModeratorEndorsed`), delegate to `EndorsementService::decide(..., ApprovalStage::OsaReview, ...)`. `@nudge`: collect `checklistItems` pending+digital names, `Notification::send($request->organization->officers, new DocumentsMissing($request, $names))`.
`ChecklistController@update`: validate `status => in:submitted,verified,pending`, `notes => nullable|string`; call `$checklist->verify($item, $notes)` when `verified`, else plain `$item->update(...)`; `return back()`.
`PacketReadyForPhysicalStage` / `DocumentsMissing`: same database-channel shape as `StatusChanged` (`DocumentsMissing` takes `public array $missing` and joins it into the message).
`osa-admin/queue.blade.php`: filter form (status select over all 9 statuses, type select, org select, date range) + table with org, title, type, activity date, completeness fraction (`{{ $r->checklistItems->where('is_physical', false)->whereIn('status', ['submitted','verified'])->count() }}/{{ $r->checklistItems->where('is_physical', false)->count() }}`), late-filed amber badge when `$r->submitted_at?->gt($r->date_start->copy()->subDays(3))`, status pill, Review link.
`osa-admin/show.blade.php`: full packet — request fields, participants, schedule, per-item file-cards with document links (`Storage::url`), per-card verify form (`PATCH` status select + notes + button), OSA Form 3 summary + its approval state, Start review / Request revisions / Deny buttons, Nudge-missing-docs button, and (once `docs_complete`) the Task 13 slip button placeholder.

- [ ] **Step 3: Run tests, commit**

Run: `php artisan test` — PASS.
```bash
git add -A && git commit -m "feat: OSA admin review queue, checklist verification, docs-complete auto-advance"
```

---

### Task 12: OSA Director notation

**Files:**
- Create: `app/Http/Controllers/OsaDirector/NotationController.php`, `resources/views/osa-director/queue.blade.php` (replace placeholder), `resources/views/osa-director/show.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/OsaDirector/NotationTest.php`

**Interfaces:**
- Consumes: `EndorsementService::decide` (stage `OsaDirectorNotation` → approval sets `awaiting_physical`, per Task 9's match arm).
- Produces: routes `GET /osa-director/queue` (requests with status `docs_complete`), `GET /osa-director/requests/{activityRequest}` (`osa-director.requests.show`), `POST /osa-director/requests/{activityRequest}/decision` (`osa-director.requests.decide`).

- [ ] **Step 1: Failing test**

```php
it('director notation moves the request to awaiting_physical', function () {
    $d = User::factory()->create(['role' => Role::OsaDirector]);
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::DocsComplete]);
    $this->actingAs($d)->post(route('osa-director.requests.decide', $r), ['decision' => 'approved'])
        ->assertRedirect();
    expect($r->refresh()->status)->toBe(ActivityStatus::AwaitingPhysical)
        ->and($r->approvals()->sole()->stage->value)->toBe('osa_director_notation');
});

it('director cannot note a request that is not docs_complete', function () {
    $d = User::factory()->create(['role' => Role::OsaDirector]);
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::Submitted]);
    $this->actingAs($d)->post(route('osa-director.requests.decide', $r), ['decision' => 'approved'])
        ->assertStatus(422);
});
```
Run — FAIL.

- [ ] **Step 2: Implement**

`NotationController`: `index` = `ActivityRequest::status(ActivityStatus::DocsComplete)->with('organization')->paginate(15)`; `show` mirrors the admin packet view read-only; `decide` guards `status === DocsComplete`, uses `DecisionRequest`, calls `EndorsementService::decide($r, $user, ApprovalStage::OsaDirectorNotation, ...)`. Views reuse the admin table/packet layouts with a "Note and release for physical signing" primary button.

- [ ] **Step 3: Run tests, commit**

Run: `php artisan test` — PASS.
```bash
git add -A && git commit -m "feat: OSA Director digital notation stage"
```

---

### Task 13: Reference slips & PDF exports

**Files:**
- Create: `app/Services/ReferenceSlipService.php`, `app/Services/PdfExportService.php`, `app/Http/Controllers/ReferenceSlipController.php`, `resources/views/pdf/reference-slip.blade.php`, `resources/views/pdf/osa-form-3.blade.php`
- Modify: `routes/web.php`, `osa-admin/show.blade.php` + `org/requests/show.blade.php` (slip section), `app/Http/Controllers/OsaAdmin/ReviewQueueController.php` (mark-approved action)
- Test: `tests/Feature/ReferenceSlipTest.php`

**Interfaces:**
- Consumes: `ReferenceSlip` model, dompdf (`Barryvdh\DomPDF\Facade\Pdf`), `EndorsementService` status arms.
- Produces:
  - `ReferenceSlipService::generate(ActivityRequest $request): ReferenceSlip` — idempotent (`firstOrCreate`); code format `OSA-{year}-{0-padded request id, 5 digits}` e.g. `OSA-2026-00042`. Allowed once status ∈ (`docs_complete`, `awaiting_physical`).
  - `ReferenceSlipService::markClaimed(ReferenceSlip $slip): void` — stamps `claimed_at`.
  - `PdfExportService::referenceSlip(ActivityRequest $request): \Symfony\Component\HttpFoundation\Response` (streams download `reference-slip-{code}.pdf`); `PdfExportService::osaForm3(OsaForm3 $form): Response`.
  - Routes: `POST osa-admin/requests/{activityRequest}/slip` (`osa-admin.slip.generate`), `POST osa-admin/slips/{referenceSlip}/claim` (`osa-admin.slip.claim`), `GET requests/{activityRequest}/slip.pdf` (`slip.pdf`, auth + `ActivityRequestPolicy@view`), `GET osa-form-3/{osaForm3}.pdf` (`osa-form-3.pdf`), `POST osa-admin/requests/{activityRequest}/approve` (`osa-admin.requests.approve`: `awaiting_physical → approved` after wet signatures, optional scanned-copy upload field `final_scan` stored as a `DocumentUpload`-style file at `uploads/{id}/final-scan.*` — store path on… keep simple: store as checklist note) — final scan is stored via `$request->documentUploads()->create` reusing `document_type = 'schedule_file'`? **No** — instead add nothing to the enum; store the scan path in a new nullable `activity_requests.final_scan_path` column (tiny migration in this task).

- [ ] **Step 1: Failing tests**

```php
it('generates an idempotent reference code once docs are complete', function () {
    $a = admin();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::DocsComplete]);
    $this->actingAs($a)->post(route('osa-admin.slip.generate', $r))->assertRedirect();
    $this->actingAs($a)->post(route('osa-admin.slip.generate', $r));
    expect(\App\Models\ReferenceSlip::count())->toBe(1)
        ->and($r->referenceSlip->reference_code)->toBe('OSA-'.now()->year.'-'.str_pad($r->id, 5, '0', STR_PAD_LEFT));
});

it('refuses slip generation before docs_complete', function () {
    $a = admin();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::OsaReviewing]);
    $this->actingAs($a)->post(route('osa-admin.slip.generate', $r))->assertStatus(422);
});

it('downloads the slip pdf', function () {
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id, 'status' => ActivityStatus::AwaitingPhysical]);
    app(\App\Services\ReferenceSlipService::class)->generate($r);
    $this->actingAs($u)->get(route('slip.pdf', $r))
        ->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('marks approved after physical stage', function () {
    $a = admin();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::AwaitingPhysical]);
    $this->actingAs($a)->post(route('osa-admin.requests.approve', $r));
    expect($r->refresh()->status)->toBe(ActivityStatus::Approved);
});
```
Run — FAIL.

- [ ] **Step 2: Implement**

`ReferenceSlipService`:
```php
public function generate(ActivityRequest $request): ReferenceSlip
{
    abort_unless(in_array($request->status, [ActivityStatus::DocsComplete, ActivityStatus::AwaitingPhysical], true), 422);
    return $request->referenceSlip()->firstOrCreate([], [
        'reference_code' => 'OSA-'.now()->year.'-'.str_pad($request->id, 5, '0', STR_PAD_LEFT),
        'generated_at' => now(),
    ]);
}

public function markClaimed(ReferenceSlip $slip): void
{
    $slip->update(['claimed_at' => now()]);
}
```
`PdfExportService`:
```php
public function referenceSlip(ActivityRequest $request)
{
    $request->loadMissing(['organization', 'referenceSlip', 'checklistItems']);
    return Pdf::loadView('pdf.reference-slip', ['request' => $request])
        ->download("reference-slip-{$request->referenceSlip->reference_code}.pdf");
}

public function osaForm3(OsaForm3 $form)
{
    $form->loadMissing(['complianceItems', 'activityRequest.organization', 'moderator']);
    return Pdf::loadView('pdf.osa-form-3', ['form' => $form])
        ->download("osa-form-3-{$form->activityRequest->id}.pdf");
}
```
PDF views: inline CSS only (dompdf — no Tailwind). `pdf/reference-slip.blade.php` uses the full navy-gradient hero + Playfair-italic wordmark treatment (Design System §4 — print exports are where the full branding appears): navy header block (`background: #0B1930` fallback since dompdf gradients are unreliable — solid navy-900 is acceptable), gold `Ref.` line, request summary table, and the physical-items checklist to bring to the counter. `pdf/osa-form-3.blade.php` mirrors the CHED table layout with the moderator's recommending-approval line (name + approved date, or blank line if pending).
Migration: `$table->string('final_scan_path')->nullable();` on `activity_requests`.
`ReviewQueueController@approve`: guard `AwaitingPhysical`, validate optional `final_scan => file|mimes:pdf,jpg,jpeg,png|max:10240`, store to `uploads/{id}/final-scan…` into `final_scan_path`, set `Approved`, notify officers (`StatusChanged`). `ReferenceSlipController`: `generate` (delegates + back), `claim` (`markClaimed` + back), `slipPdf`/`osaForm3Pdf` (authorize `view` on the request, then delegate). Wire buttons into `osa-admin/show.blade.php` (Generate slip when `docs_complete|awaiting_physical`, Mark claimed when slip exists, Approve form with file input when `awaiting_physical`) and `org/requests/show.blade.php` (Download slip link when slip exists; hero already shows the code from Task 7).

- [ ] **Step 3: Run tests, commit**

Run: `php artisan test` — PASS.
```bash
git add -A && git commit -m "feat: reference slips, PDF exports, physical-stage approval"
```

---

### Task 14: OSA Admin configuration (orgs, users, moderator assignments)

**Files:**
- Create: `app/Http/Controllers/OsaAdmin/OrganizationController.php`, `app/Http/Controllers/OsaAdmin/UserController.php`, `app/Http/Controllers/OsaAdmin/ModeratorAssignmentController.php`, `resources/views/osa-admin/organizations/index.blade.php`, `resources/views/osa-admin/organizations/edit.blade.php`, `resources/views/osa-admin/users/index.blade.php`, `resources/views/osa-admin/users/create.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/OsaAdmin/ConfigTest.php`

**Interfaces:**
- Consumes: `Organization`, `User`, `Role` (Task 2).
- Produces: resource routes under `osa-admin`: `organizations` (index/store/update — name + accreditation_status select `accredited|suspended|inactive`), `users` (index/create/store — name, email, role select, organization select shown for org_officer; password auto-generated with `Str::password(12)` and displayed once in the success flash), `POST organizations/{organization}/moderators` + `DELETE organizations/{organization}/moderators/{user}` (attach/detach moderator assignments). Org officers of non-accredited orgs are blocked from creating requests (guard added to `ActivityRequestPolicy@create`: `$user->organization?->accreditation_status === 'accredited'`, enforced in `Org\ActivityRequestController@create/@store` via `$this->authorize('create', ActivityRequest::class)`).

- [ ] **Step 1: Failing tests**

```php
it('admin provisions an org officer account', function () {
    $a = admin();
    $org = Organization::factory()->create();
    $this->actingAs($a)->post(route('osa-admin.users.store'), [
        'name' => 'Org Prez', 'email' => 'prez@adzu.edu.ph',
        'role' => 'org_officer', 'organization_id' => $org->id,
    ])->assertRedirect();
    $u = User::where('email', 'prez@adzu.edu.ph')->sole();
    expect($u->role)->toBe(Role::OrgOfficer)->and($u->organization_id)->toBe($org->id);
});

it('admin assigns and removes a moderator', function () {
    $a = admin();
    $org = Organization::factory()->create();
    $m = User::factory()->create(['role' => Role::Moderator]);
    $this->actingAs($a)->post(route('osa-admin.moderators.store', $org), ['user_id' => $m->id]);
    expect($org->moderators()->count())->toBe(1);
    $this->actingAs($a)->delete(route('osa-admin.moderators.destroy', [$org, $m]));
    expect($org->moderators()->count())->toBe(0);
});

it('suspended orgs cannot submit requests', function () {
    $u = officer();
    $u->organization->update(['accreditation_status' => 'suspended']);
    $this->actingAs($u)->get(route('org.requests.create'))->assertForbidden();
});
```
Run — FAIL.

- [ ] **Step 2: Implement**

`UserController@store`:
```php
$data = $req->validate([
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email', 'unique:users'],
    'role' => ['required', new Enum(Role::class)],
    'organization_id' => ['nullable', 'exists:organizations,id', 'required_if:role,org_officer'],
]);
$password = Str::password(12);
User::create([...$data, 'password' => Hash::make($password)]);
return redirect()->route('osa-admin.users.index')
    ->with('status', "Account created. Temporary password: {$password}");
```
`OrganizationController`: standard index (table with officer count, moderator names, status pill using accreditation status → map `accredited→verified`-style green pill), store/update with `name => required`, `accreditation_status => in:accredited,suspended,inactive`. `ModeratorAssignmentController`: `store` validates `user_id => exists:users,id` + that the user's role is `moderator`, then `syncWithoutDetaching`; `destroy` detaches. `ActivityRequestPolicy@create`:
```php
public function create(User $user): bool
{
    return $user->role === Role::OrgOfficer
        && $user->organization?->accreditation_status === 'accredited';
}
```
Add `$this->authorize('create', ActivityRequest::class);` at the top of `Org\ActivityRequestController@create` and `@store`. Views: white-card tables + modest forms following the same patterns as earlier admin views; org edit page hosts the moderator-assignment section (current moderators with remove buttons + a select of all moderator-role users + Assign button).

- [ ] **Step 3: Run tests, commit**

Run: `php artisan test` — PASS.
```bash
git add -A && git commit -m "feat: OSA admin configuration — orgs, account provisioning, moderator assignments"
```

---

### Task 15: Notification bell & records/archive

**Files:**
- Create: `app/Http/Controllers/NotificationController.php`, `app/Http/Controllers/ArchiveController.php`, `resources/views/archive/index.blade.php`, `resources/views/components/notification-bell.blade.php`
- Modify: `resources/views/layouts/app.blade.php` (bell in nav), `routes/web.php`
- Test: `tests/Feature/ArchiveTest.php`, `tests/Feature/NotificationBellTest.php`

**Interfaces:**
- Consumes: database notifications (Task 9/11), `ActivityRequest` scopes.
- Produces:
  - `GET /notifications` (`notifications.index`: paginated list, marks all read on view via `$request->user()->unreadNotifications->markAsRead()`); bell component shows `auth()->user()->unreadNotifications()->count()` badge (gold dot) and links to the index. Each notification row links to the request using its role-appropriate show route (org → `org.requests.show`, moderator → `moderator.requests.show`, admin → `osa-admin.requests.show`, director → `osa-director.requests.show` — resolve via a `match` on role in the view).
  - `GET /archive` (`archive.index`, roles `osa_admin,osa_director`): terminal-status requests (`approved`, `denied`) searchable by `?q=` (title/venue `LIKE`), org filter, year filter; shows per-request turnaround (`submitted_at` → final approval `acted_at` diff in days) and links to uploaded documents. Org officers get their own history implicitly via `org.dashboard` (all statuses already listed) — no separate org archive page.

- [ ] **Step 1: Failing tests**

```php
it('shows unread count and marks read on view', function () {
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);
    $u->notify(new \App\Notifications\StatusChanged($r));
    expect($u->unreadNotifications()->count())->toBe(1);
    $this->actingAs($u)->get(route('notifications.index'))->assertOk()->assertSee($r->title);
    expect($u->fresh()->unreadNotifications()->count())->toBe(0);
});

it('archive searches approved requests by title', function () {
    $a = admin();
    $hit = ActivityRequest::factory()->create(['status' => ActivityStatus::Approved, 'title' => 'Coastal Cleanup']);
    $miss = ActivityRequest::factory()->create(['status' => ActivityStatus::Approved, 'title' => 'Chess Cup']);
    ActivityRequest::factory()->create(['status' => ActivityStatus::Submitted, 'title' => 'Coastal Pending']);
    $this->actingAs($a)->get(route('archive.index', ['q' => 'Coastal']))
        ->assertOk()->assertSee('Coastal Cleanup')->assertDontSee('Chess Cup')->assertDontSee('Coastal Pending');
});
```
Run — FAIL.

- [ ] **Step 2: Implement**

`ArchiveController@index`:
```php
$requests = ActivityRequest::query()
    ->whereIn('status', [ActivityStatus::Approved, ActivityStatus::Denied])
    ->when($req->q, fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', "%{$t}%")->orWhere('venue', 'like', "%{$t}%")))
    ->when($req->organization_id, fn ($q, $o) => $q->where('organization_id', $o))
    ->when($req->year, fn ($q, $y) => $q->whereYear('date_start', $y))
    ->with(['organization', 'approvals', 'documentUploads'])
    ->latest('date_start')->paginate(20)->withQueryString();
```
Turnaround in the view: `$r->submitted_at?->diffInDays($r->approvals->max('acted_at'))`. Bell component (Alpine-free, plain link):
```blade
<a href="{{ route('notifications.index') }}" class="relative p-2">
    <x-heroicon-o-bell class="w-5 h-5 text-slate-500" />
    @if(auth()->user()->unreadNotifications()->count())
        <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-gold-500"></span>
    @endif
</a>
```
Routes: `notifications.index` for all auth roles; `archive.index` behind `role:osa_admin,osa_director`. Add Archive to the OSA nav links.

- [ ] **Step 3: Run tests, commit**

Run: `php artisan test` — PASS.
```bash
git add -A && git commit -m "feat: notification bell + searchable records archive"
```

---

### Task 16: Demo seeder & full-system verification

**Files:**
- Create: `database/seeders/DemoSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`, `README.md` (setup + demo logins)
- Test: full suite + manual walkthrough

**Interfaces:**
- Consumes: everything.
- Produces: `php artisan migrate:fresh --seed` yields a demo environment: 3 orgs (e.g. "Computer Science Society", "Red Cross Youth Council", "SACSI"), 1 officer each (`org1@osa.test` … pw `password`), 2 moderators (`mod1@osa.test`, `mod2@osa.test`) with assignments, `admin@osa.test` (osa_admin), `director@osa.test` (osa_director), plus sample requests seeded at each pipeline stage (one draft-equivalent submitted, one endorsed, one in review, one docs_complete, one approved off-campus with OSA Form 3 + slip).

- [ ] **Step 1: Write the seeder**

```php
public function run(): void
{
    $orgs = collect(['Computer Science Society', 'Red Cross Youth Council', 'SACSI'])
        ->map(fn ($n) => Organization::create(['name' => $n]));
    $orgs->each(function ($org, $i) {
        User::factory()->create(['name' => "{$org->name} Officer", 'email' => 'org'.($i + 1).'@osa.test',
            'role' => Role::OrgOfficer, 'organization_id' => $org->id, 'password' => Hash::make('password')]);
    });
    $mods = collect([1, 2])->map(fn ($i) => User::factory()->create([
        'name' => "Moderator {$i}", 'email' => "mod{$i}@osa.test",
        'role' => Role::Moderator, 'password' => Hash::make('password')]));
    $orgs[0]->moderators()->attach($mods[0]);
    $orgs[1]->moderators()->attach($mods[0]);
    $orgs[2]->moderators()->attach($mods[1]);
    User::factory()->create(['name' => 'OSA Admin', 'email' => 'admin@osa.test', 'role' => Role::OsaAdmin, 'password' => Hash::make('password')]);
    User::factory()->create(['name' => 'OSA Director', 'email' => 'director@osa.test', 'role' => Role::OsaDirector, 'password' => Hash::make('password')]);

    $checklist = app(ChecklistService::class);
    foreach ([ActivityStatus::Submitted, ActivityStatus::ModeratorEndorsed, ActivityStatus::OsaReviewing,
              ActivityStatus::DocsComplete, ActivityStatus::Approved] as $i => $status) {
        $r = ActivityRequest::factory()
            ->{$i >= 3 ? 'offCampus' : 'state'}($i >= 3 ? [] : ['activity_type' => 'in_campus'])
            ->create(['organization_id' => $orgs[$i % 3]->id, 'status' => $status,
                      'submitted_by' => $orgs[$i % 3]->officers()->first()->id]);
        $checklist->seedFor($r);
        if (in_array($status, [ActivityStatus::DocsComplete, ActivityStatus::Approved], true)) {
            $r->checklistItems()->where('is_physical', false)->update(['status' => 'verified']);
            app(ReferenceSlipService::class)->generate(
                $status === ActivityStatus::Approved ? tap($r)->update(['status' => ActivityStatus::DocsComplete]) : $r);
            $r->update(['status' => $status]);
        }
    }
}
```
(If the factory-state chaining line is awkward, split into two plain loops — in-campus for the first three, off-campus for the last two. Correctness over cleverness.)

- [ ] **Step 2: Verify end-to-end**

```bash
php artisan migrate:fresh --seed
php artisan test
php artisan serve
```
Manual walkthrough (record results honestly): log in as `org1@osa.test` → submit an off-campus request → upload participant list → fill OSA Form 3; log in as `mod1@osa.test` → endorse + approve Form 3; `admin@osa.test` → start review → verify all digital items → confirm auto `docs_complete`; `director@osa.test` → note; `admin@osa.test` → generate slip → download PDF → mark claimed → approve. Confirm the org sees each status change + notifications throughout.

- [ ] **Step 3: Write README setup section and commit**

README covers: XAMPP MySQL prerequisite, `composer install`, `.env` copy, `php artisan key:generate`, `migrate --seed`, `storage:link`, `npm run build`, `php artisan serve`, and the demo logins table above.
```bash
git add -A && git commit -m "feat: demo seeder, README setup guide"
```

---

## Self-Review (performed at planning time)

- **Spec coverage:** §5.1 Digital Activity Request → Task 6; §5.2 Checklist & Upload Center → Tasks 5, 7, 8; §5.3 Status dashboards → Tasks 7, 11; §5.4 Physical Document Bridge → Task 13; §5.5 Notifications → Tasks 9, 11, 15; §5.6 Records/Archive → Task 15; §5.7 Admin config incl. 3-day rule → Tasks 6 (validation), 11 (late flag), 14 (org/user/moderator management). Wet-signature constraints → checklist template physical flags (Task 5) + no upload path for consent/medical (enum, Task 4). OSA Form 3 single moderator approval → Task 10. Director notation → Task 12. Document template management (§5.7) is intentionally reduced to the fixed checklist templates in `ChecklistService::template()` — editable templates deferred, matching "Deferred / Not in v1" spirit; flag to the user if they want CRUD-able templates.
- **Type consistency:** `ChecklistService` method names (`seedFor`, `isComplete`, `markSubmitted`, `verify`, `itemNameFor`, `template`) used identically in Tasks 5, 6, 8, 10, 11, 16. `EndorsementService::decide(request, actor, stage, decision, remarks)` used in Tasks 9, 11, 12. Status/stage/decision enum string values match the ERD everywhere.
- **Dependencies recommended beyond the architecture doc** (all in scope): `blade-ui-kit/blade-heroicons` (icons the Design System's `<x-icon>` recipes assume), Pest (Breeze's default test scaffold). Deliberately **not** added: `spatie/laravel-permission` (enum suffices for 4 fixed roles), Livewire/Inertia (spec forbids SPA), spatie/medialibrary (one disk, six document types — native storage is enough).
