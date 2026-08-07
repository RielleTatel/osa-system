<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function officer(): \App\Models\User
{
    return \App\Models\User::factory()->create([
        'role' => \App\Enums\Role::OrgOfficer,
        'organization_id' => \App\Models\Organization::factory(),
    ]);
}

function admin(): \App\Models\User
{
    return \App\Models\User::factory()->create(['role' => \App\Enums\Role::OsaAdmin]);
}

function director(): \App\Models\User
{
    return \App\Models\User::factory()->create(['role' => \App\Enums\Role::OsaDirector]);
}

function moderatorFor(\App\Models\ActivityRequest $request): \App\Models\User
{
    $moderator = \App\Models\User::factory()->create(['role' => \App\Enums\Role::Moderator]);
    $moderator->moderatedOrganizations()->attach($request->organization_id);

    return $moderator;
}

/**
 * @return array<string, mixed>
 */
function validRequestPayload(array $overrides = []): array
{
    return array_merge([
        'activity_type' => 'in_campus',
        'title' => 'Acquaintance Party',
        'nature_of_activity' => 'Social',
        'nature_of_engagement' => 'organizer',
        'date_start' => now()->addDays(5)->toDateString(),
        'date_end' => now()->addDays(5)->toDateString(),
        'time_of_activity' => '13:00',
        'venue' => 'Carlos Dominguez Hall',
        'purpose' => 'Welcome the freshmen.',
        'participants' => [['full_name' => 'Juan Dela Cruz', 'year_course' => 'BSCS 2']],
        'schedule_items' => [['time_slot' => '13:00-14:00', 'description' => 'Opening program']],
    ], $overrides);
}
