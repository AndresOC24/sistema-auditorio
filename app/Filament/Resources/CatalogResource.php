<?php

namespace App\Filament\Resources;

use App\Models\Contracts\Catalog;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Base de los cinco catálogos editables.
 *
 * Todos comparten la misma forma —un nombre único y un interruptor de
 * activo— así que el formulario, la tabla y la protección de borrado viven
 * aquí una sola vez. Los recursos concretos solo declaran su modelo, sus
 * etiquetas y lo que tengan de más.
 */
abstract class CatalogResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Qué se registra en este catálogo y para qué sirve.
     *
     * Se muestra bajo el título del listado, que es donde se aterriza al
     * entrar al módulo. Cada catálogo concreto la define.
     */
    abstract public static function getModuleDescription(): string;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->validationMessages([
                    'unique' => 'Ya existe un registro con ese nombre.',
                ])
                ->autofocus(),

            ...static::extraFormComponents(),

            Toggle::make('is_active')
                ->label('Activo')
                ->helperText('Al desactivarlo deja de ofrecerse al capturar eventos nuevos, pero se conserva en los eventos ya registrados.')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                ...static::extraTableColumns(),

                TextColumn::make('events_count')
                    ->label('Eventos')
                    ->counts('events')
                    ->sortable()
                    ->alignEnd()
                    ->tooltip('Cuántos eventos registrados lo usan'),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),

                ...static::extraTableFilters(),
            ])
            ->recordActions([
                EditAction::make(),
                static::guardedDeleteAction(),
            ])
            // Sin borrado masivo a propósito: el borrado de un catálogo en uso
            // debe fallar con un mensaje que explique por qué, y en lote eso
            // se convierte en un error a medias difícil de interpretar.
            ->toolbarActions([])
            ->emptyStateHeading('Todavía no hay registros')
            ->emptyStateDescription('Agrega el primero para poder usarlo al registrar eventos.');
    }

    /**
     * Borrar un catálogo que ya aparece en eventos históricos rompería el
     * reporte, así que se ofrece desactivar en su lugar.
     */
    protected static function guardedDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->before(function (DeleteAction $action, Catalog $record): void {
                $usos = $record->usageCount();

                if ($usos === 0) {
                    return;
                }

                Notification::make()
                    ->warning()
                    ->title('No se puede eliminar')
                    ->body(sprintf(
                        '"%s" se usa en %d evento%s registrado%s. Desactívalo en su lugar: '.
                        'dejará de ofrecerse al capturar eventos nuevos y el histórico queda intacto.',
                        $record->displayName(),
                        $usos,
                        $usos === 1 ? '' : 's',
                        $usos === 1 ? '' : 's',
                    ))
                    ->persistent()
                    ->send();

                $action->cancel();
            });
    }

    /**
     * Campos propios del catálogo, entre el nombre y el interruptor.
     *
     * @return array<int, mixed>
     */
    protected static function extraFormComponents(): array
    {
        return [];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function extraTableColumns(): array
    {
        return [];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function extraTableFilters(): array
    {
        return [];
    }
}
