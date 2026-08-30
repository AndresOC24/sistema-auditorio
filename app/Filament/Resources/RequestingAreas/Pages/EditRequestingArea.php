<?php

namespace App\Filament\Resources\RequestingAreas\Pages;

use App\Filament\Resources\RequestingAreas\RequestingAreaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRequestingArea extends EditRecord
{
    protected static string $resource = RequestingAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
