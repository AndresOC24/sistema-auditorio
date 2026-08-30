<?php

namespace App\Exceptions;

use Exception;

/**
 * Las ventanas de tiempo de un evento están en un estado que el motor de
 * cálculo no puede interpretar.
 */
class InvalidEventSchedule extends Exception
{
    public static function eventEndsBeforeItStarts(): self
    {
        return new self('La hora de fin del evento debe ser posterior a la de inicio.');
    }

    public static function itEndsBeforeItStarts(): self
    {
        return new self('La hora de fin de TI debe ser posterior a la de inicio.');
    }

    public static function spansMultipleDays(): self
    {
        return new self(
            'Un evento debe empezar y terminar el mismo día. Si el montaje cruza la '.
            'medianoche, regístralo como dos eventos.',
        );
    }
}
