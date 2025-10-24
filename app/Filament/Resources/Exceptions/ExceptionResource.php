<?php

namespace App\Filament\Resources\Exceptions;

use App\Filament\Resources\Exceptions\Infolists\ExceptionInfolist;
use App\Filament\Resources\Exceptions\Pages\ListExceptions;
use App\Filament\Resources\Exceptions\Pages\ViewException;
use App\Filament\Resources\Exceptions\Tables\ExceptionsTable;
use App\Models\Exception;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ExceptionResource extends Resource
{
    protected static ?string $model = Exception::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ExceptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExceptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExceptions::route('/'),
            'view' => ViewException::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with('site');

        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        // Se non è admin, filtra solo le eccezioni dei suoi siti
        if ($user && ! $user->isAdmin()) {
            $query->whereHas('site', function ($q) use ($user) {
                $q->whereHas('users', function ($q2) use ($user) {
                    $q2->where('users.id', $user->id);
                });
            });
        }

        return $query;
    }
}
