<?php

namespace App\Filament\Resources\EventTypes\Pages;

use App\Filament\Resources\Concerns\ListsCatalogRecords;
use App\Filament\Resources\EventTypes\EventTypeResource;
use Filament\Resources\Pages\ListRecords;

class ListEventTypes extends ListRecords
{
    use ListsCatalogRecords;

    protected static string $resource = EventTypeResource::class;
}
