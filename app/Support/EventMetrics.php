<?php

namespace App\Support;

/**
 * Resultado del motor de cálculo para un evento.
 *
 * Todo se expresa en minutos enteros a propósito: el dashboard los suma sobre
 * rangos de meses y las horas decimales arrastrarían errores de redondeo.
 */
final readonly class EventMetrics
{
    public function __construct(
        public int $eventDurationMinutes,
        public int $itDurationMinutes,
        public int $eventOutsideHoursMinutes,
        public int $itOutsideHoursMinutes,
        public bool $exceedsWorkingHours,
        public bool $onNonWorkingDay,
    ) {}

    /**
     * En la forma que esperan las columnas de la tabla events.
     *
     * @return array<string, int|bool>
     */
    public function toAttributes(): array
    {
        return [
            'event_duration_minutes' => $this->eventDurationMinutes,
            'it_duration_minutes' => $this->itDurationMinutes,
            'event_outside_hours_minutes' => $this->eventOutsideHoursMinutes,
            'it_outside_hours_minutes' => $this->itOutsideHoursMinutes,
            'exceeds_working_hours' => $this->exceedsWorkingHours,
            'on_non_working_day' => $this->onNonWorkingDay,
        ];
    }

    /**
     * Horas de TI fuera de horario, para mostrar en pantalla.
     */
    public function itOutsideHours(): float
    {
        return round($this->itOutsideHoursMinutes / 60, 2);
    }
}
