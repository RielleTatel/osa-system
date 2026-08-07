<?php

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Enums\Role;
use App\Models\ActivityRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityRequest>
 */
class ActivityRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'submitted_by' => User::factory()->state(['role' => Role::OrgOfficer]),
            'activity_type' => 'in_campus',
            'title' => fake()->sentence(3),
            'nature_of_activity' => fake()->randomElement(['Seminar', 'Workshop', 'Outreach', 'Sports', 'Social']),
            'nature_of_engagement' => 'organizer',
            'main_organizer' => null,
            'date_start' => now()->addDays(7)->toDateString(),
            'date_end' => now()->addDays(7)->toDateString(),
            'time_of_activity' => '09:00',
            'venue' => fake()->randomElement(['AVR 1', 'Carlos Dominguez Hall', 'Gym', 'Covered Court']),
            'purpose' => fake()->paragraph(),
            'status' => ActivityStatus::Submitted,
            'submitted_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ActivityRequest $request) {
            // Keep the submitting officer attached to the request's organization.
            $request->submitter->update(['organization_id' => $request->organization_id]);
        });
    }

    public function offCampus(): static
    {
        return $this->state(['activity_type' => 'off_campus']);
    }
}
