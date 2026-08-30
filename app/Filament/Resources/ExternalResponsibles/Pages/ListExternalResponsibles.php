<?php

namespace App\Filament\Resources\ExternalResponsibles\Pages;

use App\Filament\Resources\Concerns\ListsCatalogRecords;
use App\Filament\Resources\ExternalResponsibles\ExternalResponsibleResource;
use Filament\Resources\Pages\ListRecords;

class ListExternalResponsibles extends ListRecords
{
    use ListsCatalogRecords;

    protected static string $resource = ExternalResponsibleResource::class;
}
