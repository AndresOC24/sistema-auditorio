<?php

namespace Database\Seeders;

use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    /**
     * Deja creada la configuración de horario con los valores por defecto:
     * 8:30 a 16:30, de lunes a viernes.
     *
     * Idempotente y no destructivo: si el horario ya se editó desde el panel,
     * lo respeta.
     */
    public function run(): void
    {
        $schedule = WorkSchedule::current();

        $this->command->info(sprintf(
            'Horario laboral: %s a %s, %s.',
            $schedule->start_time,
            $schedule->end_time,
            $schedule->workingDaysLabel(),
        ));
    }
}
