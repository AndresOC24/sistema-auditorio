<?php

namespace App\Observers;

use App\Exceptions\InvalidEventSchedule;
use App\Models\Event;
use App\Services\WorkingHoursCalculator;

/**
 * Mantiene coherentes las columnas calculadas de un evento.
 *
 * Validación y cálculo viven los dos en `saving` y en ese orden: primero se
 * rechaza lo que el motor no sabe interpretar, y solo entonces se calcula.
 */
class EventObserver
{
    public function saving(Event $event): void
    {
        $this->guardAgainstInvalidSchedule($event);

        $event->forceFill(
            WorkingHoursCalculator::make()->forEvent($event)->toAttributes(),
        );
    }

    /**
     * @throws InvalidEventSchedule
     */
    private function guardAgainstInvalidSchedule(Event $event): void
    {
        if ($event->ended_at->lessThanOrEqualTo($event->started_at)) {
            throw InvalidEventSchedule::eventEndsBeforeItStarts();
        }

        if ($event->it_ended_at->lessThanOrEqualTo($event->it_started_at)) {
            throw InvalidEventSchedule::itEndsBeforeItStarts();
        }

        $day = $event->started_at->toDateString();

        $sameDay = $event->ended_at->toDateString() === $day
            && $event->it_started_at->toDateString() === $day
            && $event->it_ended_at->toDateString() === $day;

        if (! $sameDay) {
            throw InvalidEventSchedule::spansMultipleDays();
        }
    }
}
