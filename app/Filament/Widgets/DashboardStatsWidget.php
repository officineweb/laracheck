<?php

namespace App\Filament\Widgets;

use App\Models\Exception;
use App\Models\Outage;
use App\Models\Site;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|array|null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 3,
    ];

    protected function getStats(): array
    {
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        // Query per Sites e Outages
        $sitesQuery = Site::query()->where('enable_uptime_check', true);
        $outagesQuery = Outage::query();
        $exceptionsQuery = Exception::query();

        if (! $user->isAdmin()) {
            $userSiteIds = $user->sites()->pluck('sites.id');
            $sitesQuery->whereIn('id', $userSiteIds);
            $outagesQuery->whereIn('site_id', $userSiteIds);
            $exceptionsQuery->whereIn('site_id', $userSiteIds);
        }

        // Uptime Stats
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

        // Exception Stats
        $openExceptions = (clone $exceptionsQuery)->where('status', Exception::OPEN)->count();
        $exceptionsLast30Days = (clone $exceptionsQuery)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            // Riga 1
            Stat::make('Sites Online (monitored sites)', $sitesOnline . ' / ' . $monitoredSites)
                ->description($sitesDown > 0 ? $sitesDown . ' currently down' : 'All systems operational')
                ->descriptionIcon($sitesDown > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($sitesDown > 0 ? 'danger' : 'success'),

            Stat::make('Active Outages', $activeOutages)
                ->description($activeOutages > 0 ? 'Require attention' : 'No active outages')
                ->descriptionIcon($activeOutages > 0 ? 'heroicon-m-signal-slash' : 'heroicon-m-signal')
                ->color($activeOutages > 0 ? 'danger' : 'success'),

            Stat::make('Last Outages (30d)', $totalOutages)
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-signal-slash')
                ->color('warning'),

            // Riga 2
            Stat::make('Last Downtime (30d)', $downtimeFormatted)
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),

            Stat::make('Open Exceptions', $openExceptions)
                ->description('Require attention')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Last Exceptions (30d)', $exceptionsLast30Days)
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-bug-ant')
                ->color('info'),
        ];
    }
}
