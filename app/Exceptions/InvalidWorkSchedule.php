<?php

namespace App\Exceptions;

use Exception;

/**
 * El horario laboral quedaría en un estado que el motor de cálculo no puede
 * interpretar. Se lanza desde el modelo para que la invariante se sostenga
 * venga el cambio de donde venga: panel, seeder, tinker o una prueba.
 */
class InvalidWorkSchedule extends Exception
{
    public static function endBeforeStart(string $startTime, string $endTime): self
    {
        return new self(sprintf(
            'La hora de salida (%s) debe ser posterior a la de entrada (%s).',
            $endTime,
            $startTime,
        ));
    }

    public static function withoutWorkingDays(): self
    {
        return new self('Debe quedar al menos un día laboral seleccionado.');
    }

    public static function unknownWeekday(int $day): self
    {
        return new self(sprintf(
            'El día %d no es válido. Se esperaba numeración ISO-8601, de 1 (lunes) a 7 (domingo).',
            $day,
        ));
    }
}
