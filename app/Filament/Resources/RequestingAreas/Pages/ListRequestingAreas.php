<?php

namespace App\Filament\Resources\RequestingAreas\Pages;

use App\Filament\Resources\Concerns\ListsCatalogRecords;
use App\Filament\Resources\RequestingAreas\RequestingAreaResource;
use Filament\Resources\Pages\ListRecords;

class ListRequestingAreas extends ListRecords
{
    use ListsCatalogRecords;

    protected static string $resource = RequestingAreaResource::class;
}
