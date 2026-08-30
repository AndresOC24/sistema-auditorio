<?php

namespace App\Filament\Resources\ItPeople\Pages;

use App\Filament\Resources\ItPeople\ItPersonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateItPerson extends CreateRecord
{
    protected static string $resource = ItPersonResource::class;
}
