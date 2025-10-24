<?php

namespace App\Filament\Resources\Outages\Pages;

use App\Filament\Resources\Outages\OutageResource;
use Filament\Resources\Pages\ListRecords;

class ListOutages extends ListRecords
{
    protected static string $resource = OutageResource::class;
}

