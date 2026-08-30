<?php

namespace App\Enums;

/**
 * Día de la semana en numeración ISO-8601: lunes es 1 y domingo es 7.
 *
 * Se eligió ISO sobre la numeración de Carbon (domingo = 0) porque el horario
 * laboral se define de lunes a viernes y con ISO eso es un rango contiguo,
 * [1..5], en lugar de partirse alrededor del cero.
 */
enum Weekday: int
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    public function label(): string
    {
        return match ($this) {
            self::Monday => 'Lunes',
            self::Tuesday => 'Martes',
            self::Wednesday => 'Miércoles',
            self::Thursday => 'Jueves',
            self::Friday => 'Viernes',
            self::Saturday => 'Sábado',
            self::Sunday => 'Domingo',
        };
    }

    /**
     * Opciones para el selector de días laborales del panel.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $day): array => [$day->value => $day->label()])
            ->all();
    }

    /**
     * De lunes a viernes, el horario laboral por defecto del auditorio.
     *
     * @return array<int, int>
     */
    public static function weekdayValues(): array
    {
        return [
            self::Monday->value,
            self::Tuesday->value,
            self::Wednesday->value,
            self::Thursday->value,
            self::Friday->value,
        ];
    }
}
