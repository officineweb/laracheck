<?php

namespace App\Filament\Widgets;

use App\Models\Outage;
use App\Models\Site;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UptimeStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|array|null $columns = 3;

    protected function getStats(): array
    {
        $user = auth()->user();

        // Costruisci query base in base al ruolo
        $sitesQuery = Site::query()->where('enable_uptime_check', true);
        $outagesQuery = Outage::query();

        if (! $user->isAdmin()) {
            // Filtra per i siti dell'utente
            $userSiteIds = $user->sites()->pluck('sites.id');

            $sitesQuery->whereIn('id', $userSiteIds);
            $outagesQuery->whereIn('site_id', $userSiteIds);
        }

        $monitoredSites = (clone $sitesQuery)->count();
        $sitesOnline = (clone $sitesQuery)->where('is_online', true)->count();
        $sitesDown = (clone $sitesQuery)->where('is_online', false)->count();
        $activeOutages = (clone $outagesQuery)->whereNull('resolved_at')->count();

        $totalOutages = (clone $outagesQuery)->where('occurred_at', '>=', now()->subDays(30))->count();
        $totalDowntime = (clone $outagesQuery)
            ->whereNotNull('resolved_at')
            ->where('occurred_at', '>=', now()->subDays(30))
            ->get()
            ->sum(function ($outage) {
                return $outage->occurred_at->diffInMinutes($outage->resolved_at);
            });

        $hours = floor($totalDowntime / 60);
        $minutes = $totalDowntime % 60;
        $downtimeFormatted = $hours . 'h ' . $minutes . 'm';

        return [
            Stat::make('Sites Online', $sitesOnline . ' / ' . $monitoredSites)
                ->description($sitesDown > 0 ? $sitesDown . ' currently down' : 'All systems operational')
                ->descriptionIcon($sitesDown > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($sitesDown > 0 ? 'danger' : 'success'),

            Stat::make('Active Outages', $activeOutages)
                ->description($activeOutages > 0 ? 'Require attention' : 'No active outages')
                ->descriptionIcon($activeOutages > 0 ? 'heroicon-m-signal-slash' : 'heroicon-m-signal')
                ->color($activeOutages > 0 ? 'danger' : 'success'),

            Stat::make('Outages (30d)', $totalOutages)
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),

            Stat::make('Total Downtime (30d)', $downtimeFormatted)
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
        ];
    }
}
