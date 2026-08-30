<?php

namespace App\Filament\Resources\RequestingAreas;

use App\Filament\Resources\CatalogResource;
use App\Filament\Resources\RequestingAreas\Pages\CreateRequestingArea;
use App\Filament\Resources\RequestingAreas\Pages\EditRequestingArea;
use App\Filament\Resources\RequestingAreas\Pages\ListRequestingAreas;
use App\Models\RequestingArea;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Support\Icons\Heroicon;

class RequestingAreaResource extends CatalogResource
{
    protected static ?string $model = RequestingArea::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $modelLabel = 'Área solicitante';

    protected static ?string $pluralModelLabel = 'Áreas solicitantes';

    public static function getModuleDescription(): string
    {
        return 'Las áreas de la institución que solicitan el auditorio: rectorado, bienestar estudiantil, comunicación, investigación. Cada evento se registra a nombre del área que lo pidió, para saber cuánto tiempo de TI consume cada una.';
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRequestingAreas::route('/'),
            'create' => CreateRequestingArea::route('/create'),
            'edit' => EditRequestingArea::route('/{record}/edit'),
        ];
    }
}
