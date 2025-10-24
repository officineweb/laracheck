<?php

namespace App\Filament\Resources\Exceptions\Pages;

use App\Filament\Resources\Exceptions\ExceptionResource;
use Filament\Resources\Pages\ListRecords;

class ListExceptions extends ListRecords
{
    protected static string $resource = ExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Exceptions are read-only, created via API only
        ];
    }
}
