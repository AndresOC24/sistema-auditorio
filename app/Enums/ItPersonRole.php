<?php

namespace App\Enums;

/**
 * Rol con el que una persona de TI participa en un evento.
 *
 * El sobrepaso de horario laboral aplica a administrativos y no a becarios.
 * El sistema no hace nada con esa distinción al calcular —las horas fuera de
 * horario son a nivel de evento— pero la conserva para interpretar el reporte.
 */
enum ItPersonRole: string
{
    case Administrativo = 'administrativo';
    case Becario = 'becario';

    public function label(): string
    {
        return match ($this) {
            self::Administrativo => 'Administrativo',
            self::Becario => 'Becario',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->label()])
            ->all();
    }
}
