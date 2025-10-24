<?php

namespace App\Filament\Resources\Exceptions\Tables;

use App\Models\Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ExceptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->prefix('#')
                    ->size('xs')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->sortable()
                    ->hidden(),
                TextColumn::make('created_at')
                    ->label('Logged at')
                    ->since()
                    ->description(fn($record) => \App\Helpers\DateHelper::format($record->created_at))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('site.name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        500 => 'danger',
                        400, 401, 403, 404 => 'warning',
                        default => 'info',
                    })
                    ->default(500)
                    ->sortable(),
                TextColumn::make('exception')
                    ->label('Error')
                    ->size('xs')
                    ->fontFamily(FontFamily::Mono)
                    ->limit(80)
                    ->wrap()
                    ->lineClamp(2)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'OPEN' => 'danger',
                        'FIXED' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('sites')
                    ->native(false)
                    ->options(fn() => \App\Models\Site::pluck('name', 'id')->toArray())
                    ->multiple()
                    ->query(function ($query, $state) {
                        if (! empty($state['values'])) {
                            $query->whereIn('site_id', $state['values']);
                        }

                        return $query;
                    }),
                SelectFilter::make('status')
                    ->options([
                        Exception::OPEN => 'Open',
                        Exception::FIXED => 'Fixed',
                    ])
                    ->multiple(),
                Filter::make('created_at')
                    ->label('Date Range')
                    ->indicateUsing(fn($data) => ($data['from'] || $data['to']) ? 'Date range: ' . (($data['from']) ? 'from ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y') . ' ' : '') . (($data['to']) ? 'to ' . \Carbon\Carbon::parse($data['to'])->format('d/m/Y') : '') : null)
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('to'),
                    ])
                    ->query(function ($query, $data) {
                        if (! empty($data['from'])) {
                            $query->where('created_at', '>=', $data['from']);
                        }

                        if (! empty($data['to'])) {
                            $query->where('created_at', '<=', $data['to']);
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_as_fixed')
                        ->label('Mark as Fixed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark exceptions as fixed')
                        ->modalDescription('Are you sure you want to mark the selected exceptions as fixed?')
                        ->modalSubmitActionLabel('Yes, mark as fixed')
                        ->action(function (Collection $records) {
                            $openExceptions = $records->filter(fn(Exception $exception) => $exception->status === Exception::OPEN);

                            $openExceptions->each(fn(Exception $exception) => $exception->markAs(Exception::FIXED));

                            Notification::make()
                                ->success()
                                ->title('Exceptions marked as fixed')
                                ->body($openExceptions->count() . ' exception(s) have been marked as fixed.')
                                ->send();
                        }),
                ]),
            ]);
    }
}
