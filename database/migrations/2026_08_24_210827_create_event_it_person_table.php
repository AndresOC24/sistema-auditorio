<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Personas de TI que participaron en cada evento.
     *
     * El rol se guarda aquí y no se lee de it_people a propósito: el catálogo
     * rota cada semestre y un becario puede pasar a administrativo. Si el
     * reporte leyera el rol actual de la persona, ese cambio reescribiría el
     * histórico de todos sus eventos pasados.
     *
     * Lleva clave primaria autoincremental porque el repeater de Filament
     * necesita identificar cada renglón.
     */
    public function up(): void
    {
        Schema::create('event_it_person', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('it_person_id')->constrained()->restrictOnDelete();
            $table->string('role');
            $table->timestamps();

            $table->unique(['event_id', 'it_person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_it_person');
    }
};
