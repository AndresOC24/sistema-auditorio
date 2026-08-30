<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Rango de fechas por defecto del reporte.
 *
 * Vive en su propia clase y no en el trait de los widgets porque la pantalla
 * del dashboard también lo necesita para los valores iniciales del filtro, y
 * PHP no admite llamar métodos estáticos sobre un trait.
 */
final class ReportingPeriod
{
    /**
     * El último semestre: es la unidad en la que TI reporta, y muestra algo
     * útil desde el primer día del mes, cosa que un "mes en curso" no haría.
     */
    public static function defaultStart(): CarbonImmutable
    {
        return CarbonImmutable::now()->subMonths(6)->startOfDay();
    }

    public static function defaultEnd(): CarbonImmutable
    {
        return CarbonImmutable::now()->endOfDay();
    }
}
