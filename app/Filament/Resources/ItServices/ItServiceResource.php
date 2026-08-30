<?php

namespace App\Filament\Resources\ItServices;

use App\Filament\Resources\CatalogResource;
use App\Filament\Resources\ItServices\Pages\CreateItService;
use App\Filament\Resources\ItServices\Pages\EditItService;
use App\Filament\Resources\ItServices\Pages\ListItServices;
use App\Models\ItService;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Support\Icons\Heroicon;

class ItServiceResource extends CatalogResource
{
    protected static ?string $model = ItService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $modelLabel = 'Servicio de TI';

    protected static ?string $pluralModelLabel = 'Servicios de TI';

    public static function getModuleDescription(): string
    {
        return 'Lo que TI presta durante un evento: sonido, proyección, transmisión en vivo, grabación. Al registrar un evento se marcan todos los servicios que se dieron en él.';
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListItServices::route('/'),
            'create' => CreateItService::route('/create'),
            'edit' => EditItService::route('/{record}/edit'),
        ];
    }
}
