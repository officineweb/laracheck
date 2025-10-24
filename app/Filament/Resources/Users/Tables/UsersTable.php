<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sites_count')
                    ->label('Assigned Sites')
                    ->getStateUsing(function ($record) {
                        if ($record->is_admin) {
                            return '(all)';
                        }

                        return $record->sites()->count();
                    })
                    ->badge()
                    ->color(fn($record) => $record->is_admin ? 'success' : 'info'),
                IconColumn::make('receive_email')
                    ->label('Email Notifications')
                    ->boolean()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
