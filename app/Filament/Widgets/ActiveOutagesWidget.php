<?php

namespace App\Filament\Widgets;

use App\Models\Outage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ActiveOutagesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        $query = Outage::whereNull('resolved_at')
            ->with('site')
            ->latest('occurred_at');

        if (! $user->isAdmin()) {
            $userSiteIds = $user->sites()->pluck('sites.id');
            $query->whereIn('site_id', $userSiteIds);
        }

        return $table
            ->query($query)
            ->heading('🚨 Active Outages')
            ->columns([
                TextColumn::make('site.name')
                    ->label('Site')
                    ->badge()
                    ->color('danger')
                    ->url(fn ($record) => $record->site->url, true),
                TextColumn::make('occurred_at')
                    ->label('Down Since')
                    ->dateTime()
                    ->since()
                    ->description(fn ($record) => $record->occurred_at->format('M d, Y - H:i:s')),
                TextColumn::make('duration')
                    ->label('Downtime')
                    ->getStateUsing(fn ($record) => 'Ongoing: '.$record->occurred_at->diffForHumans())
                    ->badge()
                    ->color('danger')
                    ->weight('bold'),
                TextColumn::make('response_code')
                    ->label('Status Code')
                    ->badge()
                    ->color('warning')
                    ->placeholder('N/A'),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->placeholder('No error message')
                    ->wrap(),
            ])
            ->emptyStateHeading('No Active Outages')
            ->emptyStateDescription('All monitored sites are currently online! 🎉')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
