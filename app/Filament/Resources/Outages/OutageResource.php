<?php

namespace App\Filament\Resources\Outages;

use App\Filament\Resources\Outages\Pages\ListOutages;
use App\Filament\Resources\Outages\Tables\OutagesTable;
use App\Models\Outage;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class OutageResource extends Resource
{
    protected static ?string $model = Outage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-signal-slash';

    protected static ?string $navigationLabel = 'Outages';

    protected static ?string $modelLabel = 'Outage';

    protected static ?string $pluralModelLabel = 'Outages';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return OutagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutages::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        // Se non è admin, filtra solo gli outages dei suoi siti
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
