<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Personas del área de TI que cubren eventos. El listado rota cada
     * semestre, por eso se desactivan en lugar de borrarse: los eventos
     * históricos deben seguir apuntando a quien participó.
     */
    public function up(): void
    {
        Schema::create('it_people', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('role');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_people');
    }
};
