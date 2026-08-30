<?php

namespace App\Filament\Resources\ItServices\Pages;

use App\Filament\Resources\Concerns\ListsCatalogRecords;
use App\Filament\Resources\ItServices\ItServiceResource;
use Filament\Resources\Pages\ListRecords;

class ListItServices extends ListRecords
{
    use ListsCatalogRecords;

    protected static string $resource = ItServiceResource::class;
}
