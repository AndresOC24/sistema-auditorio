<?php

namespace App\Filament\Resources\ItServices\Pages;

use App\Filament\Resources\ItServices\ItServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditItService extends EditRecord
{
    protected static string $resource = ItServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
