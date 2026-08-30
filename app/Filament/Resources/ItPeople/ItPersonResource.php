<?php

namespace App\Filament\Resources\ItPeople;

use App\Enums\ItPersonRole;
use App\Filament\Resources\CatalogResource;
use App\Filament\Resources\ItPeople\Pages\CreateItPerson;
use App\Filament\Resources\ItPeople\Pages\EditItPerson;
use App\Filament\Resources\ItPeople\Pages\ListItPeople;
use App\Models\ItPerson;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\PageRegistration;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ItPersonResource extends CatalogResource
{
    protected static ?string $model = ItPerson::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'Persona de TI';

    protected static ?string $pluralModelLabel = 'Personas de TI';

    /**
     * @return array<int, mixed>
     */
    protected static function extraFormComponents(): array
    {
        return [
            Select::make('role')
                ->label('Rol')
                ->options(ItPersonRole::options())
                ->required()
                ->native(false)
                ->helperText('Es el rol vigente. El rol con el que participó en cada evento se guarda aparte y no cambia al editarlo aquí.'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function extraTableColumns(): array
    {
        return [
            TextColumn::make('role')
                ->label('Rol')
                ->badge()
                ->formatStateUsing(fn (ItPersonRole $state): string => $state->label())
                ->color(fn (ItPersonRole $state): string => match ($state) {
                    ItPersonRole::Administrativo => 'primary',
                    ItPersonRole::Becario => 'gray',
                })
                ->sortable(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function extraTableFilters(): array
    {
        return [
            SelectFilter::make('role')
                ->label('Rol')
                ->options(ItPersonRole::options()),
        ];
    }

    public static function getModuleDescription(): string
    {
        return 'El equipo de TI que cubre los eventos, con su rol vigente. El listado rota cada semestre: a quien deja el equipo desactívalo en lugar de borrarlo, así se conserva su participación en los eventos ya registrados.';
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListItPeople::route('/'),
            'create' => CreateItPerson::route('/create'),
            'edit' => EditItPerson::route('/{record}/edit'),
        ];
    }
}
