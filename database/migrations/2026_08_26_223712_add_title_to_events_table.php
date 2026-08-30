<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nombre del evento, tal como aparece en el calendario.
     *
     * Es un dato distinto del tipo: "1er Simposio de Cáncer Hereditario" es el
     * nombre, y su tipo sería "simposio". Sin él, dos eventos del mismo tipo en
     * la misma semana son indistinguibles en el listado.
     *
     * Se agrega en tres pasos porque ya hay eventos registrados: se crea
     * nullable, se rellena con el tipo de cada evento como valor provisional, y
     * recién entonces se vuelve obligatoria.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('id');
        });

        DB::statement(<<<'SQL'
            UPDATE events
            JOIN event_types ON event_types.id = events.event_type_id
            SET events.title = event_types.name
            WHERE events.title IS NULL
        SQL);

        Schema::table('events', function (Blueprint $table): void {
            $table->string('title')->nullable(false)->change();
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropIndex(['title']);
            $table->dropColumn('title');
        });
    }
};
