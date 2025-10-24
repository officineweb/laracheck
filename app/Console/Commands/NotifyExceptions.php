<?php

namespace App\Console\Commands;

use App\Models\Exception;
use App\Models\Site;
use App\Notifications\ExceptionsBatchNotification;
use Illuminate\Console\Command;

class NotifyExceptions extends Command
{
    protected $signature = 'exceptions:notify';

    protected $description = 'Send batch notifications for new exceptions (every 5 minutes)';

    public function handle(): void
    {
        // Prendi tutte le eccezioni degli ultimi 5 minuti non ancora notificate
        // Escludi quelle auto-fixate (4xx errors)
        $exceptions = Exception::whereNull('notified_at')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->where('status', Exception::OPEN)
            ->with('site')
            ->get();

        if ($exceptions->isEmpty()) {
            $this->info('✅ No new exceptions to notify.');

            return;
        }

        // Raggruppa per site
        $exceptionsBySite = $exceptions->groupBy('site_id');

        foreach ($exceptionsBySite as $siteId => $siteExceptions) {
            $site = Site::find($siteId);

            if (! $site) {
                continue;
            }

            // Invia notifica al site (email/slack/discord)
            if ($site->receive_email || $site->slack_webhook || $site->discord_webhook) {
                $site->notify(new ExceptionsBatchNotification($siteExceptions));
            }

            // Marca come notificate
            $siteExceptions->each(function ($exception) {
                $exception->update(['notified_at' => now()]);
            });

            $this->info("📧 Notified {$siteExceptions->count()} exceptions for site: {$site->name}");
        }

        $this->info("🎉 Total exceptions notified: {$exceptions->count()}");
    }
}
