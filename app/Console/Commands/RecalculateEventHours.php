<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\WorkSchedule;
use Illuminate\Console\Command;

class RecalculateEventHours extends Command
{
    protected $signature = 'auditorio:recalcular-horas';

    protected $description = 'Recalcula duraciones y horas fuera de horario de todos los eventos registrados';

    /**
     * Se corre después de cambiar el horario laboral: las columnas calculadas
     * de los eventos ya registrados quedaron con el horario anterior y hay que
     * ponerlas al día.
     */
    public function handle(): int
    {
        $schedule = WorkSchedule::current();

        $this->info(sprintf('Horario vigente: %s a %s', $schedule->start_time, $schedule->end_time));
        $this->info(sprintf('Días laborales: %s', $schedule->workingDaysLabel()));

        $total = Event::count();

        if ($total === 0) {
            $this->comment('No hay eventos registrados.');

            return self::SUCCESS;
        }

        $changed = 0;

        $this->withProgressBar($total, function ($progress) use (&$changed): void {
            Event::query()->chunkById(200, function ($events) use (&$changed, $progress): void {
                foreach ($events as $event) {
                    // El observer recalcula en `saving`; save() solo escribe
                    // si alguna métrica cambió de verdad.
                    $event->save();

                    if ($event->wasChanged()) {
                        $changed++;
                    }

                    $progress->advance();
                }
            });
        });

        $this->newLine(2);
        $this->info(sprintf('%d de %d eventos actualizados.', $changed, $total));

        return self::SUCCESS;
    }
}
