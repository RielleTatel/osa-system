<?php

namespace Database\Seeders;

use App\Enums\ActivityStatus;
use App\Enums\ChecklistStatus;
use App\Enums\Role;
use App\Models\ActivityRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\ChecklistService;
use App\Services\ReferenceSlipService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $checklist = app(ChecklistService::class);
        $slips = app(ReferenceSlipService::class);

        $orgs = collect(['Computer Science Society', 'Red Cross Youth Council', 'SACSI'])
            ->map(fn ($name) => Organization::create(['name' => $name]));

        $orgs->each(function (Organization $org, int $i) {
            User::create([
                'name' => "{$org->name} Officer",
                'email' => 'org'.($i + 1).'@osa.test',
                'role' => Role::OrgOfficer,
                'organization_id' => $org->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        });

        $moderators = collect([1, 2])->map(fn ($i) => User::create([
            'name' => "Moderator {$i}",
            'email' => "mod{$i}@osa.test",
            'role' => Role::Moderator,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]));

        $orgs[0]->moderators()->attach($moderators[0]);
        $orgs[1]->moderators()->attach($moderators[0]);
        $orgs[2]->moderators()->attach($moderators[1]);

        User::create(['name' => 'OSA Admin', 'email' => 'admin@osa.test', 'role' => Role::OsaAdmin,
            'password' => Hash::make('password'), 'email_verified_at' => now()]);
        User::create(['name' => 'OSA Director', 'email' => 'director@osa.test', 'role' => Role::OsaDirector,
            'password' => Hash::make('password'), 'email_verified_at' => now()]);

        // Sample requests at each pipeline stage.
        $stages = [
            ['status' => ActivityStatus::Submitted, 'type' => 'in_campus', 'title' => 'Acquaintance Party'],
            ['status' => ActivityStatus::ModeratorEndorsed, 'type' => 'in_campus', 'title' => 'Tech Talk Series'],
            ['status' => ActivityStatus::OsaReviewing, 'type' => 'off_campus', 'title' => 'Community Outreach'],
            ['status' => ActivityStatus::DocsComplete, 'type' => 'off_campus', 'title' => 'Coastal Cleanup Drive'],
            ['status' => ActivityStatus::Approved, 'type' => 'off_campus', 'title' => 'Leadership Camp'],
        ];

        foreach ($stages as $i => $stage) {
            $org = $orgs[$i % 3];
            $officer = $org->officers()->first();

            $request = ActivityRequest::create([
                'organization_id' => $org->id,
                'submitted_by' => $officer->id,
                'activity_type' => $stage['type'],
                'title' => $stage['title'],
                'nature_of_activity' => 'Seminar',
                'nature_of_engagement' => 'organizer',
                'date_start' => now()->addDays(10 + $i)->toDateString(),
                'date_end' => now()->addDays(10 + $i)->toDateString(),
                'time_of_activity' => '09:00',
                'venue' => $stage['type'] === 'off_campus' ? 'Bolong Beach' : 'AVR 1',
                'purpose' => 'Demonstration activity seeded for the OSA system walkthrough.',
                'status' => $stage['status'],
                'submitted_at' => now()->subDays(5 - $i),
            ]);

            $request->participants()->create(['full_name' => 'Maria Santos', 'year_course' => 'BSCS 3']);
            $request->scheduleItems()->create(['time_slot' => '09:00-11:00', 'description' => 'Main program']);
            $checklist->seedFor($request);

            if (in_array($stage['status'], [ActivityStatus::DocsComplete, ActivityStatus::Approved], true)) {
                $request->checklistItems()->where('is_physical', false)->update(['status' => ChecklistStatus::Verified]);
                // Generate a slip while the request is in a slip-eligible state.
                $request->update(['status' => ActivityStatus::DocsComplete]);
                $slips->generate($request);
                $request->update(['status' => $stage['status']]);
            }
        }
    }
}
