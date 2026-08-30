<?php

namespace App\Support;

/**
 * Presentación de duraciones.
 *
 * Todo el sistema guarda minutos enteros; esto es lo único que los traduce a
 * algo legible, para que la tabla, el formulario y el dashboard digan las
 * cosas igual.
 */
final class Duration
{
    /**
     * "6 h 30 min", "45 min", "8 h".
     */
    public static function humanize(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return match (true) {
            $hours === 0 => sprintf('%d min', $rest),
            $rest === 0 => sprintf('%d h', $hours),
            default => sprintf('%d h %d min', $hours, $rest),
        };
    }

    /**
     * Horas con un decimal, para las gráficas: los ejes no admiten "6 h 30 min".
     */
    public static function toHours(int $minutes): float
    {
        return round($minutes / 60, 1);
    }
}
