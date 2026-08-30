<?php

namespace App\Filament\Resources\ExternalResponsibles;

use App\Filament\Resources\CatalogResource;
use App\Filament\Resources\ExternalResponsibles\Pages\CreateExternalResponsible;
use App\Filament\Resources\ExternalResponsibles\Pages\EditExternalResponsible;
use App\Filament\Resources\ExternalResponsibles\Pages\ListExternalResponsibles;
use App\Models\ExternalResponsible;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Support\Icons\Heroicon;

class ExternalResponsibleResource extends CatalogResource
{
    protected static ?string $model = ExternalResponsible::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $modelLabel = 'Responsable externo';

    protected static ?string $pluralModelLabel = 'Responsables externos';

    public static function getModuleDescription(): string
    {
        return 'Las personas ajenas a TI que quedan a cargo de cada evento. No hace falta cargarlas aquí de antemano: al registrar un evento puedes escribir un nombre nuevo y se crea al vuelo, quedando disponible desde entonces.';
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListExternalResponsibles::route('/'),
            'create' => CreateExternalResponsible::route('/create'),
            'edit' => EditExternalResponsible::route('/{record}/edit'),
        ];
    }
}
