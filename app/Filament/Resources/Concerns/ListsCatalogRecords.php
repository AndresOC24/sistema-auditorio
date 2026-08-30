<?php

namespace App\Filament\Resources\Concerns;

use Filament\Actions\CreateAction;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Cabecera común de los listados de catálogo.
 *
 * Muestra bajo el título la descripción que declara el recurso, para que al
 * entrar al módulo quede claro qué se registra ahí sin tener que abrir el
 * formulario.
 */
trait ListsCatalogRecords
{
    public function getSubheading(): string|Htmlable|null
    {
        return static::getResource()::getModuleDescription();
    }

    /**
     * @return array<int, CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
