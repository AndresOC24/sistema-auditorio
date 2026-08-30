<?php

namespace Database\Seeders;

use App\Enums\ItPersonRole;
use App\Models\EventType;
use App\Models\ItService;
use App\Models\RequestingArea;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Valores de arranque para los catálogos editables.
     *
     * Son un punto de partida, no una lista cerrada: todos se editan desde el
     * panel. Las personas de TI se dejan fuera a propósito porque el listado
     * rota cada semestre y hay que capturarlo a mano.
     *
     * Idempotente: volver a correrlo no duplica ni pisa lo que ya se editó.
     */
    public function run(): void
    {
        $eventTypes = [
            'Conferencia',
            'Graduación',
            'Congreso',
            'Taller',
            'Ceremonia institucional',
            'Presentación cultural',
            'Reunión de personal',
            'Defensa de tesis',
        ];

        $requestingAreas = [
            'Rectorado',
            'Vicerrectorado',
            'Secretaría Académica',
            'Bienestar Estudiantil',
            'Recursos Humanos',
            'Investigación',
            'Extensión Universitaria',
            'Comunicación Institucional',
        ];

        $itServices = [
            'Sonido',
            'Proyección',
            'Transmisión en vivo',
            'Grabación de video',
            'Videoconferencia',
            'Soporte de laptop y presentaciones',
            'Montaje y desmontaje',
        ];

        foreach ($eventTypes as $name) {
            EventType::firstOrCreate(['name' => $name]);
        }

        foreach ($requestingAreas as $name) {
            RequestingArea::firstOrCreate(['name' => $name]);
        }

        foreach ($itServices as $name) {
            ItService::firstOrCreate(['name' => $name]);
        }

        $this->command->info(sprintf(
            'Catálogos: %d tipos de evento, %d áreas, %d servicios. Los roles disponibles son %s.',
            EventType::count(),
            RequestingArea::count(),
            ItService::count(),
            collect(ItPersonRole::cases())->map(fn (ItPersonRole $r): string => $r->label())->join(' y '),
        ));
    }
}
