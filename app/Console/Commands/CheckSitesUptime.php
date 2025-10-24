<?php

namespace App\Console\Commands;

use App\Models\Outage;
use App\Models\Site;
use App\Notifications\OutageDetectedNotification;
use App\Notifications\OutageResolvedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckSitesUptime extends Command
{
    protected $signature = 'sites:check-uptime';

    protected $description = 'Check all sites uptime status';

    public function handle(): int
    {
        $sites = Site::where('enable_uptime_check', true)->get();

        if ($sites->isEmpty()) {
            $this->info('No sites to check.');

            return self::SUCCESS;
        }

        $this->info('Checking ' . $sites->count() . ' sites...');

        $bar = $this->output->createProgressBar($sites->count());

        foreach ($sites as $site) {
            $this->checkSite($site);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('All sites checked successfully!');

        return self::SUCCESS;
    }

    protected function checkSite(Site $site): void
    {
        $url = $site->check_url ?: $site->url;

        if (! $url) {
            return;
        }

        try {
            $response = Http::timeout(10)->get($url);

            $isOnline = $response->successful();
            $responseCode = $response->status();
            $errorMessage = null;
        } catch (\Exception $e) {
            $isOnline = false;
            $responseCode = null;
            $errorMessage = $e->getMessage();
        }

        $wasOnline = $site->is_online;

        $site->update([
            'is_online' => $isOnline,
            'checked_at' => now(),
        ]);

        // Site went down
        if ($wasOnline && ! $isOnline) {
            $outage = Outage::create([
                'site_id' => $site->id,
                'occurred_at' => now(),
                'response_code' => $responseCode,
                'error_message' => $errorMessage,
            ]);

            // Notify site
            $site->notify(new OutageDetectedNotification($outage));

            // Notify assigned users
            foreach ($site->users as $user) {
                $user->notify(new OutageDetectedNotification($outage));
            }

            // Send email to specified outage email address
            if ($site->email_outage) {
                \Illuminate\Support\Facades\Notification::route('mail', $site->email_outage)
                    ->notify(new OutageDetectedNotification($outage));
            }

            $this->error('⚠️  ' . $site->name . ' is DOWN');
        }

        // Site came back online
        if (! $wasOnline && $isOnline) {
            $outage = $site->activeOutage();

            if ($outage) {
                $outage->update(['resolved_at' => now()]);

                // Notify site
                $site->notify(new OutageResolvedNotification($outage));

                // Notify assigned users
                foreach ($site->users as $user) {
                    $user->notify(new OutageResolvedNotification($outage));
                }

                // Send email to specified resolved email address
                if ($site->email_resolved) {
                    \Illuminate\Support\Facades\Notification::route('mail', $site->email_resolved)
                        ->notify(new OutageResolvedNotification($outage));
                }

                $this->info('✅  ' . $site->name . ' is BACK ONLINE');
            }
        }
    }
}
