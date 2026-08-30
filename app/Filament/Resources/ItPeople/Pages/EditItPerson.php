<?php

namespace App\Filament\Resources\ItPeople\Pages;

use App\Filament\Resources\ItPeople\ItPersonResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditItPerson extends EditRecord
{
    protected static string $resource = ItPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
