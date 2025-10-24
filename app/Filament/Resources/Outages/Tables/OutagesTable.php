<?php

namespace App\Filament\Resources\Outages\Tables;

use App\Models\Site;
use Carbon\Carbon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OutagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Occurred at')
                    ->since()
                    ->sortable()
                    ->description(fn($record) => \App\Helpers\DateHelper::format($record->occurred_at)),
                TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                IconColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->boolean(),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->sortable()
                    ->weight(fn($record) => $record->resolved_at ? 'normal' : 'bold')
                    ->color(fn($record) => $record->resolved_at ? 'gray' : 'danger')
                    ->description(fn($record) => ($record->resolved_at) ? 'Resolved at: ' . \App\Helpers\DateHelper::format($record->resolved_at) : null),
            ])
            ->modifyQueryUsing(fn($query) => $query->orderByRaw('resolved_at IS NULL DESC')->orderBy('occurred_at', 'desc'))
            ->defaultSort('occurred_at', 'desc')
            ->filters([
                Filter::make('in_outage')
                    ->toggle()
                    ->query(function ($query, $state) {
                        if ($state['isActive']) {
                            $query->whereNull('resolved_at');
                        }

                        return $query;
                    }),
                SelectFilter::make('sites')
                    ->multiple()
                    ->options(fn() => Site::pluck('name', 'id')->toArray())
                    ->query(function ($query, array $data) {
                        if (! empty($data['values'])) {
                            foreach ($data['values'] as $site) {
                                $query->orWhere('site_id', $site);
                            }
                        }

                        return $query;
                    }),
                Filter::make('occurred_at')
                    ->indicateUsing(fn($data) => ($data['from'] || $data['to']) ? 'Date range: ' . (($data['from']) ? 'from ' . Carbon::parse($data['from'])->format('d/m/Y') . ' ' : '') . (($data['to']) ? 'to ' . Carbon::parse($data['to'])->format('d/m/Y') : '') : null)
                    ->form([
                        \Filament\Schemas\Components\Fieldset::make('Date Range')
                            ->columns(1)
                            ->schema([
                                \Filament\Forms\Components\DatePicker::make('from'),
                                \Filament\Forms\Components\DatePicker::make('to'),
                            ]),
                    ])
                    ->query(function ($query, $data) {
                        if ($data['from']) {
                            $query->where('occurred_at', '>=', $data['from']);
                        }

                        if ($data['to']) {
                            $query->where('occurred_at', '<=', $data['to']);
                        }

                        return $query;
                    }),
            ]);
    }
}
