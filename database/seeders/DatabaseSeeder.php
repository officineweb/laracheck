<?php

namespace Database\Seeders;

use App\Models\Exception;
use App\Models\Outage;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        if (config('app.env') === 'local') {
            $this->command->info('Creating test data for local environment...');

            $users = User::factory(5)->create();
            $allUsers = User::query()->get();

            $sites = Site::factory(15)->create();

            $this->command->info('Attaching users to sites...');
            foreach ($sites as $site) {
                $owner = $users->random();
                $site->users()->attach($owner, ['is_owner' => true]);

                $collaborators = $users->except($owner->id)->random(rand(0, 2));
                foreach ($collaborators as $collaborator) {
                    if (! $site->users->contains($collaborator)) {
                        $site->users()->attach($collaborator, ['is_owner' => false]);
                    }
                }
            }

            $this->command->info('Creating exceptions...');
            $recentExceptions = collect();

            Exception::withoutEvents(function () use ($sites, &$recentExceptions) {
                foreach ($sites as $site) {
                    Exception::factory(rand(15, 30))
                        ->for($site)
                        ->fixed()
                        ->create();

                    Exception::factory(rand(3, 8))
                        ->for($site)
                        ->open()
                        ->create();

                    // Create some auto-fixed 4xx errors (client errors)
                    Exception::factory(rand(2, 5))
                        ->for($site)
                        ->clientError()
                        ->create();

                    $recentExceptions = $recentExceptions->merge(
                        $site->exceptions()->latest()->limit(3)->get()
                    );
                }
            });

            $this->command->info('Creating outages...');
            $activeOutages = collect();
            $resolvedOutages = collect();

            $sitesWithUptimeCheck = $sites->where('enable_uptime_check', true);

            foreach ($sitesWithUptimeCheck as $site) {
                $resolved = Outage::factory(rand(3, 8))
                    ->for($site)
                    ->resolved()
                    ->create();

                $resolvedOutages = $resolvedOutages->merge($resolved->take(2));

                if (rand(1, 100) > 85) {
                    $active = Outage::factory()
                        ->for($site)
                        ->active()
                        ->create();

                    $activeOutages->push($active);

                    // Update site status to offline
                    $site->update(['is_online' => false]);
                }
            }

            $this->command->info('Creating notifications...');

            foreach ($recentExceptions->random(min(15, $recentExceptions->count())) as $exception) {
                foreach ($exception->site->users as $user) {
                    $user->notify(new \App\Notifications\NewExceptionNotification($exception));
                }
            }

            foreach ($activeOutages as $outage) {
                $notificationClass = new class($outage) extends \App\Notifications\OutageDetectedNotification
                {
                    public function via($notifiable): array
                    {
                        return ['database'];
                    }
                };
                $outage->site->notify($notificationClass);
            }

            foreach ($resolvedOutages->random(min(5, $resolvedOutages->count())) as $outage) {
                $notificationClass = new class($outage) extends \App\Notifications\OutageResolvedNotification
                {
                    public function via($notifiable): array
                    {
                        return ['database'];
                    }
                };
                $outage->site->notify($notificationClass);
            }

            $this->command->info('Marking some notifications as read...');
            \Illuminate\Support\Facades\DB::table('notifications')
                ->inRandomOrder()
                ->limit(\Illuminate\Support\Facades\DB::table('notifications')->count() * 0.6)
                ->update(['read_at' => now()->subHours(rand(1, 48))]);

            $this->command->newLine();
            $this->command->info('Seeding completed successfully!');
            $this->command->table(
                ['Resource', 'Count'],
                [
                    ['Users', User::query()->count()],
                    ['Sites', Site::query()->count()],
                    ['Exceptions', Exception::query()->count()],
                    ['Outages', Outage::query()->count()],
                    ['Notifications', \Illuminate\Support\Facades\DB::table('notifications')->count()],
                    ['Unread Notifications', \Illuminate\Support\Facades\DB::table('notifications')->whereNull('read_at')->count()],
                ]
            );
        }
    }
}
