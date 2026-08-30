<?php

namespace App\Filament\Resources\ExternalResponsibles\Pages;

use App\Filament\Resources\ExternalResponsibles\ExternalResponsibleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExternalResponsible extends EditRecord
{
    protected static string $resource = ExternalResponsibleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
