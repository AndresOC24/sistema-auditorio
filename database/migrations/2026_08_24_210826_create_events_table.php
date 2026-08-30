<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eventos de auditorio cubiertos por TI.
     *
     * Cada evento lleva dos ventanas de tiempo independientes: la del evento
     * en sí y la de TI, que suele ser más amplia porque el montaje empieza
     * antes y el desmontaje termina después.
     *
     * Las columnas *_minutes y las banderas las escribe el motor de cálculo
     * (fase 3) al guardar. Se persisten en lugar de resolverse al vuelo
     * porque el dashboard las suma sobre rangos de meses enteros.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('event_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('requesting_area_id')->constrained()->restrictOnDelete();
            $table->foreignId('external_responsible_id')->constrained()->restrictOnDelete();

            $table->dateTime('started_at');
            $table->dateTime('ended_at');
            $table->dateTime('it_started_at');
            $table->dateTime('it_ended_at');

            $table->text('incidents')->nullable();

            $table->unsignedInteger('event_duration_minutes')->default(0);
            $table->unsignedInteger('it_duration_minutes')->default(0);
            $table->unsignedInteger('event_outside_hours_minutes')->default(0);
            $table->unsignedInteger('it_outside_hours_minutes')->default(0);
            $table->boolean('exceeds_working_hours')->default(false);
            $table->boolean('on_non_working_day')->default(false);

            $table->timestamps();

            $table->index('started_at');
            $table->index(['started_at', 'exceeds_working_hours']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
