<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Horario laboral del área de TI, editable desde el panel.
     *
     * Tabla de un solo registro: el cálculo de horas fuera de horario lee de
     * aquí, nunca de constantes, para que cambiar el horario sea un dato y no
     * un despliegue.
     *
     * Los días laborales se guardan en numeración ISO-8601 (lunes = 1).
     */
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table): void {
            $table->id();
            $table->time('start_time');
            $table->time('end_time');
            $table->json('working_days');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
