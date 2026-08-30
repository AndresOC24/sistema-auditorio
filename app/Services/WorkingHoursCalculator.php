<?php

namespace App\Services;

use App\Models\Event;
use App\Models\WorkSchedule;
use App\Support\EventMetrics;
use Carbon\CarbonImmutable;

/**
 * Calcula cuánto tiempo de un evento cae fuera del horario laboral.
 *
 * El resultado es una cantidad de minutos, no un sí o no: un evento de 16:00
 * a 18:00 con salida a las 16:30 aporta 90 minutos fuera, y eso es lo que se
 * acumula en el reporte.
 *
 * Todo el cálculo asume que las dos ventanas de tiempo empiezan y terminan el
 * mismo día. EventObserver rechaza los eventos que cruzan la medianoche, así
 * que aquí no hace falta recorrer día por día.
 */
final readonly class WorkingHoursCalculator
{
    public function __construct(private WorkSchedule $schedule) {}

    /**
     * Con el horario laboral vigente.
     */
    public static function make(): self
    {
        return new self(WorkSchedule::current());
    }

    /**
     * Minutos de la ventana [$start, $end] que caen fuera del horario laboral.
     *
     * Se resuelve por solapamiento: al total se le resta la parte que sí cae
     * dentro de la jornada. En un día no laboral no hay jornada con la que
     * solapar, así que el total entero cuenta como fuera.
     */
    public function minutesOutsideWorkingHours(CarbonImmutable $start, CarbonImmutable $end): int
    {
        $totalMinutes = $this->minutesBetween($start, $end);

        if ($totalMinutes <= 0) {
            return 0;
        }

        if (! $this->schedule->isWorkingDay($start)) {
            return $totalMinutes;
        }

        return $totalMinutes - $this->minutesWithinWorkingHours($start, $end);
    }

    /**
     * Minutos de la ventana que sí caen dentro de la jornada laboral.
     */
    public function minutesWithinWorkingHours(CarbonImmutable $start, CarbonImmutable $end): int
    {
        if (! $this->schedule->isWorkingDay($start)) {
            return 0;
        }

        $overlapStart = $start->max($this->schedule->startOn($start));
        $overlapEnd = $end->min($this->schedule->endOn($start));

        return $this->minutesBetween($overlapStart, $overlapEnd);
    }

    /**
     * Las seis métricas de un evento, listas para escribirse en sus columnas.
     */
    public function forEvent(Event $event): EventMetrics
    {
        $eventOutside = $this->minutesOutsideWorkingHours($event->started_at, $event->ended_at);
        $itOutside = $this->minutesOutsideWorkingHours($event->it_started_at, $event->it_ended_at);

        return new EventMetrics(
            eventDurationMinutes: $this->minutesBetween($event->started_at, $event->ended_at),
            itDurationMinutes: $this->minutesBetween($event->it_started_at, $event->it_ended_at),
            eventOutsideHoursMinutes: $eventOutside,
            itOutsideHoursMinutes: $itOutside,
            // La bandera sigue a la ventana de TI: es el tiempo que el área
            // realmente invirtió fuera de horario, que es lo que el reporte
            // necesita justificar.
            exceedsWorkingHours: $itOutside > 0,
            onNonWorkingDay: ! $this->schedule->isWorkingDay($event->started_at),
        );
    }

    /**
     * Minutos entre dos instantes, nunca negativos.
     */
    private function minutesBetween(CarbonImmutable $start, CarbonImmutable $end): int
    {
        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        return (int) $start->diffInMinutes($end);
    }
}
