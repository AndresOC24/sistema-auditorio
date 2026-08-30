<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Servicios que TI prestó en cada evento. Selección múltiple sin datos
     * adicionales, por eso es un pivote simple.
     */
    public function up(): void
    {
        Schema::create('event_it_service', function (Blueprint $table): void {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('it_service_id')->constrained()->restrictOnDelete();

            $table->primary(['event_id', 'it_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_it_service');
    }
};
