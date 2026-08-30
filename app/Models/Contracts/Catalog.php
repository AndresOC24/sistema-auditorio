<?php

namespace App\Models\Contracts;

/**
 * Catálogo editable desde el panel.
 *
 * CatalogResource necesita dos cosas de los cinco catálogos: saber cómo
 * nombrarlos y cuántos eventos los usan, que es lo que permite impedir el
 * borrado de un catálogo con histórico.
 *
 * La relación `events()` queda fuera del contrato a propósito: es HasMany en
 * unos catálogos y BelongsToMany en otros, y forzarla a un tipo común aquí
 * solo estorbaría. IsCatalog la consume desde cada modelo.
 */
interface Catalog
{
    /**
     * Cuántos eventos registrados usan este registro de catálogo.
     */
    public function usageCount(): int;

    /**
     * Nombre visible del registro.
     */
    public function displayName(): string;
}
