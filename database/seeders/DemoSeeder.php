<?php

namespace Database\Seeders;

use App\Enums\ItPersonRole;
use App\Models\Event;
use App\Models\EventType;
use App\Models\ExternalResponsible;
use App\Models\ItPerson;
use App\Models\ItService;
use App\Models\RequestingArea;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

/**
 * Un semestre de eventos verosímiles, para revisar el reporte con volumen
 * antes de tener datos reales.
 *
 * **No se llama desde DatabaseSeeder a propósito.** Estos datos son inventados
 * y no deben mezclarse con el registro real:
 *
 *     php artisan db:seed --class=DemoSeeder
 *
 * Para quitarlos: `php artisan auditorio:limpiar-demo`.
 */
class DemoSeeder extends Seeder
{
    /**
     * Marca que distingue lo sembrado de lo capturado a mano.
     */
    public const RESPONSIBLE_SUFFIX = '(demo)';

    /**
     * Nombres al estilo de los que aparecen en el calendario del auditorio.
     *
     * @var array<int, string>
     */
    private array $titles = [
        'Coro',
        'Danza',
        'Elenco de Teatro',
        'Cuadro de honor',
        'Torneo de ajedrez',
        'Taller de importaciones para estudiantes',
        'Observatorio Universitario de Igualdad de Género',
        'CDVIR capacitación y detección temprana de VIH',
        '1er Simposio de Cáncer Hereditario',
        'Jornada de inducción a primer semestre',
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('DemoSeeder no corre en producción.');

            return;
        }

        // Nunca sembrar encima de un registro real. Ya ocurrió una vez y dejó
        // 84 eventos inventados mezclados con los capturados a mano.
        $propios = Event::query()
            ->whereDoesntHave('externalResponsible', fn (Builder $query) => $query
                ->where('name', 'like', '%'.self::RESPONSIBLE_SUFFIX))
            ->count();

        if ($propios > 0) {
            $this->command->error(sprintf(
                'Hay %d evento%s capturado%s a mano en esta base. DemoSeeder no siembra encima de datos reales.',
                $propios,
                $propios === 1 ? '' : 's',
                $propios === 1 ? '' : 's',
            ));
            $this->command->line('Si de verdad quieres datos de prueba, usa una base vacía.');

            return;
        }

        $this->call(CatalogSeeder::class);
        $this->call(WorkScheduleSeeder::class);

        $people = $this->seedItPeople();
        $responsibles = $this->seedResponsibles();

        $types = EventType::pluck('id')->all();
        $areas = RequestingArea::pluck('id')->all();
        $services = ItService::pluck('id')->all();

        $created = 0;

        foreach ($this->schedule() as [$date, $eventStart, $hours, $setupMinutes, $teardownMinutes, $incident]) {
            $startedAt = $date->setTime($eventStart, 0);

            $event = Event::create([
                'title' => $this->titles[array_rand($this->titles)],
                'event_type_id' => $types[array_rand($types)],
                'requesting_area_id' => $areas[array_rand($areas)],
                'external_responsible_id' => $responsibles[array_rand($responsibles)],
                'started_at' => $startedAt,
                'ended_at' => $startedAt->addHours($hours),
                'it_started_at' => $startedAt->subMinutes($setupMinutes),
                'it_ended_at' => $startedAt->addHours($hours)->addMinutes($teardownMinutes),
                'incidents' => $incident,
            ]);

            shuffle($services);
            $event->itServices()->attach(array_slice($services, 0, random_int(1, 4)));

            foreach ($this->crewFor($people, $hours) as $personId => $role) {
                $event->itPeople()->attach($personId, ['role' => $role]);
            }

            $created++;
        }

        $this->report($created);
    }

    /**
     * Personal de TI de un semestre: dos administrativos y tres becarios.
     *
     * @return array<int, array{id: int, role: ItPersonRole}>
     */
    private function seedItPeople(): array
    {
        $roster = [
            'Ana Quispe Mamani' => ItPersonRole::Administrativo,
            'Rodrigo Terán' => ItPersonRole::Administrativo,
            'Luis Mamani Choque' => ItPersonRole::Becario,
            'Sara Choque Villca' => ItPersonRole::Becario,
            'Diego Aliaga' => ItPersonRole::Becario,
        ];

        $people = [];

        foreach ($roster as $name => $role) {
            $person = ItPerson::firstOrCreate(['name' => $name], ['role' => $role]);
            $people[] = ['id' => $person->id, 'role' => $role];
        }

        return $people;
    }

    /**
     * @return array<int, int>
     */
    private function seedResponsibles(): array
    {
        $names = [
            'Kenia Rojas',
            'Patricia Núñez',
            'Javier Ledezma',
            'Silvia Ortuño',
            'Hugo Camacho',
        ];

        return array_map(
            fn (string $name): int => ExternalResponsible::firstOrCreate([
                'name' => sprintf('%s %s', $name, self::RESPONSIBLE_SUFFIX),
            ])->id,
            $names,
        );
    }

    /**
     * Quién cubre el evento.
     *
     * Los eventos largos llevan un administrativo al frente; los cortos suelen
     * quedar en manos de becarios, que es como funciona en la práctica.
     *
     * @param  array<int, array{id: int, role: ItPersonRole}>  $people
     * @return array<int, ItPersonRole>
     */
    private function crewFor(array $people, int $hours): array
    {
        $administrativos = array_values(array_filter($people, fn (array $p): bool => $p['role'] === ItPersonRole::Administrativo));
        $becarios = array_values(array_filter($people, fn (array $p): bool => $p['role'] === ItPersonRole::Becario));

        $crew = [];

        if ($hours >= 3) {
            $elegido = $administrativos[array_rand($administrativos)];
            $crew[$elegido['id']] = $elegido['role'];
        }

        $cuantos = $hours >= 4 ? 2 : 1;
        shuffle($becarios);

        foreach (array_slice($becarios, 0, $cuantos) as $becario) {
            $crew[$becario['id']] = $becario['role'];
        }

        return $crew;
    }

    /**
     * Un semestre de agenda, con la mezcla que hace interesante el reporte:
     * la mayoría dentro del horario, algunos con montaje muy temprano, unos
     * pocos que se alargan hasta la noche y un par de sábados.
     *
     * @return array<int, array{0: CarbonImmutable, 1: int, 2: int, 3: int, 4: int, 5: string|null}>
     */
    private function schedule(): array
    {
        $incidents = [
            'Falla intermitente del micrófono inalámbrico; se reemplazó a media sesión.',
            'El expositor llegó con su laptop sin adaptador HDMI.',
            'Corte de energía de 5 minutos, se reinició la transmisión.',
            'La sala se llenó y hubo que habilitar sonido en el pasillo.',
            null, null, null, null, null, null,
        ];

        $rows = [];
        $day = CarbonImmutable::now()->subMonths(5)->startOfMonth();
        $end = CarbonImmutable::now();

        while ($day->lessThan($end)) {
            // Entre dos y cuatro eventos por semana.
            foreach (range(1, random_int(2, 4)) as $ignored) {
                $candidate = $day->addDays(random_int(0, 5));

                if ($candidate->greaterThan($end)) {
                    continue;
                }

                $rows[] = $this->describeEvent($candidate, $incidents);
            }

            // Un sábado cada cinco semanas, aproximadamente. Nunca en el
            // futuro: esto es un registro de lo ocurrido, no una agenda.
            $saturday = $day->next(CarbonImmutable::SATURDAY);

            if (random_int(1, 5) === 1 && $saturday->lessThanOrEqualTo($end)) {
                $rows[] = [$saturday, 9, 4, 60, 60, null];
            }

            $day = $day->addWeek();
        }

        // El descarte de futuros se hace aquí y no arriba porque la hora de
        // inicio se decide en describeEvent(): un día que hoy parece pasado
        // al compararlo a medianoche puede terminar a las 17:00 de esta tarde.
        return array_values(array_filter(
            $rows,
            fn (array $row): bool => $row[0]->setTime($row[1], 0)->lessThanOrEqualTo($end),
        ));
    }

    /**
     * @param  array<int, string|null>  $incidents
     * @return array{0: CarbonImmutable, 1: int, 2: int, 3: int, 4: int, 5: string|null}
     */
    private function describeEvent(CarbonImmutable $day, array $incidents): array
    {
        // La mayoría cae de lleno en horario; uno de cada cuatro lo desborda.
        $desborda = random_int(1, 4) === 1;

        $start = $desborda
            ? [7, 15, 16, 17][array_rand([7, 15, 16, 17])]
            : random_int(9, 13);

        return [
            $day,
            $start,
            random_int(1, 4),
            $desborda ? random_int(45, 120) : 30,
            $desborda ? random_int(30, 90) : 30,
            $incidents[array_rand($incidents)],
        ];
    }

    private function report(int $created): void
    {
        $this->command->newLine();
        $this->command->info(sprintf('%d eventos de demostración creados.', $created));
        $this->command->line(sprintf('Personas de TI: %d', ItPerson::count()));
        $this->command->line(sprintf('Sobrepasan el horario: %d', Event::where('exceeds_working_hours', true)->count()));
        $this->command->line(sprintf('En día no laboral: %d', Event::where('on_non_working_day', true)->count()));
        $this->command->newLine();
        $this->command->comment('Para quitarlos: php artisan auditorio:limpiar-demo');
    }
}
