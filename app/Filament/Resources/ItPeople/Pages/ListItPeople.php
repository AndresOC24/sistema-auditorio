<?php

namespace App\Filament\Resources\ItPeople\Pages;

use App\Filament\Resources\Concerns\ListsCatalogRecords;
use App\Filament\Resources\ItPeople\ItPersonResource;
use Filament\Resources\Pages\ListRecords;

class ListItPeople extends ListRecords
{
    use ListsCatalogRecords;

    protected static string $resource = ItPersonResource::class;
}
