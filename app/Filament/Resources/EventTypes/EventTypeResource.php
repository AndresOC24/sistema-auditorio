<?php

namespace App\Filament\Resources\EventTypes;

use App\Filament\Resources\CatalogResource;
use App\Filament\Resources\EventTypes\Pages\CreateEventType;
use App\Filament\Resources\EventTypes\Pages\EditEventType;
use App\Filament\Resources\EventTypes\Pages\ListEventTypes;
use App\Models\EventType;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Support\Icons\Heroicon;

class EventTypeResource extends CatalogResource
{
    protected static ?string $model = EventType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $modelLabel = 'Tipo de evento';

    protected static ?string $pluralModelLabel = 'Tipos de evento';

    public static function getModuleDescription(): string
    {
        return 'Las categorías de evento que se realizan en el auditorio: conferencia, graduación, congreso, defensa de tesis, taller. Sirven para agrupar el reporte y ver qué clase de evento consume más tiempo de TI.';
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListEventTypes::route('/'),
            'create' => CreateEventType::route('/create'),
            'edit' => EditEventType::route('/{record}/edit'),
        ];
    }
}
