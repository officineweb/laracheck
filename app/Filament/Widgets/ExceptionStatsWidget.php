<?php

namespace App\Filament\Widgets;

use App\Models\Exception;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExceptionStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|array|null $columns = 3;

    protected function getStats(): array
    {
        $user = auth()->user();

        // Costruisci query base in base al ruolo
        $exceptionsQuery = Exception::query();

        if (! $user->isAdmin()) {
            // Filtra per i siti dell'utente
            $userSiteIds = $user->sites()->pluck('sites.id');
            $exceptionsQuery->whereIn('site_id', $userSiteIds);
        }

        $openExceptions = (clone $exceptionsQuery)->where('status', Exception::OPEN)->count();

        $exceptionsLast30Days = (clone $exceptionsQuery)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            Stat::make('Total Exceptions (30d)', $exceptionsLast30Days)
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-bug-ant')
                ->color('info'),

            Stat::make('Open Exceptions', $openExceptions)
                ->description('Require attention')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
