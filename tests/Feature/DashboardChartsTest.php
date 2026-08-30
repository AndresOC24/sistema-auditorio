<?php

use App\Filament\Widgets\EventsByAreaChart;
use App\Filament\Widgets\EventsByTypeChart;
use App\Filament\Widgets\ItHoursTrendChart;
use App\Models\Event;
use App\Models\EventType;
use App\Models\RequestingArea;
use App\Models\User;
use App\Support\ChartPalette;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create(['email' => config('auditorio.admin.email')]));
});

/**
 * @return array<string, mixed>
 */
function chartData(string $widget, array $filters = []): array
{
    $instance = Livewire::test($widget, ['pageFilters' => $filters])->instance();

    return (fn () => $this->getData())->call($instance);
}

describe('eventos por tipo', function (): void {
    it('agrupa y ordena por cantidad', function (): void {
        $conferencia = EventType::factory()->create(['name' => 'Conferencia']);
        $graduacion = EventType::factory()->create(['name' => 'Graduación']);

        Event::factory()->count(3)->schedule('2026-08-25', '10:00', '12:00')
            ->create(['event_type_id' => $conferencia->id]);
        Event::factory()->schedule('2026-08-25', '10:00', '12:00')
            ->create(['event_type_id' => $graduacion->id]);

        $data = chartData(EventsByTypeChart::class, ['startDate' => '2026-08-01', 'endDate' => '2026-08-31']);

        expect($data['labels'])->toBe(['Conferencia', 'Graduación'])
            ->and($data['datasets'][0]['data'])->toBe([3, 1]);
    });

    it('deja fuera los tipos sin eventos en el rango', function (): void {
        EventType::factory()->create(['name' => 'Sin uso']);
        $usado = EventType::factory()->create(['name' => 'Usado']);

        Event::factory()->schedule('2026-08-25', '10:00', '12:00')
            ->create(['event_type_id' => $usado->id]);

        $data = chartData(EventsByTypeChart::class, ['startDate' => '2026-08-01', 'endDate' => '2026-08-31']);

        expect($data['labels'])->toBe(['Usado']);
    });

    it('devuelve series vacías sin eventos', function (): void {
        $data = chartData(EventsByTypeChart::class, ['startDate' => '2026-08-01', 'endDate' => '2026-08-31']);

        expect($data['labels'])->toBe([])
            ->and($data['datasets'][0]['data'])->toBe([]);
    });
});

describe('horas de TI por área', function (): void {
    it('suma horas de TI y ordena por tiempo, no por número de eventos', function (): void {
        $rectorado = RequestingArea::factory()->create(['name' => 'Rectorado']);
        $bienestar = RequestingArea::factory()->create(['name' => 'Bienestar']);

        // Bienestar: dos eventos cortos de 1 h de TI cada uno = 2 h.
        Event::factory()->count(2)->schedule('2026-08-25', '10:00', '11:00', '10:00', '11:00')
            ->create(['requesting_area_id' => $bienestar->id]);

        // Rectorado: un solo evento largo de 6 h de TI.
        Event::factory()->schedule('2026-08-26', '09:00', '14:00', '08:30', '14:30')
            ->create(['requesting_area_id' => $rectorado->id]);

        $data = chartData(EventsByAreaChart::class, ['startDate' => '2026-08-01', 'endDate' => '2026-08-31']);

        expect($data['labels'])->toBe(['Rectorado', 'Bienestar'])
            ->and($data['datasets'][0]['data'])->toBe([6.0, 2.0]);
    });
});

describe('horas de TI por mes', function (): void {
    it('separa los meses y expone las dos series', function (): void {
        Event::factory()->schedule('2026-07-14', '10:00', '12:00', '09:00', '13:00')->create();
        Event::factory()->schedule('2026-08-25', '09:00', '12:00', '07:00', '13:00')->create();

        $data = chartData(ItHoursTrendChart::class, ['startDate' => '2026-07-01', 'endDate' => '2026-08-31']);

        expect($data['labels'])->toHaveCount(2)
            ->and($data['datasets'])->toHaveCount(2)
            ->and($data['datasets'][0]['label'])->toBe('Horas de TI')
            ->and($data['datasets'][1]['label'])->toBe('Fuera de horario')
            ->and($data['datasets'][0]['data'])->toBe([4.0, 6.0])
            ->and($data['datasets'][1]['data'])->toBe([0.0, 1.5]);
    });

    it('ordena los meses cronológicamente', function (): void {
        Event::factory()->schedule('2026-08-25', '10:00', '12:00')->create();
        Event::factory()->schedule('2026-06-16', '10:00', '12:00')->create();
        Event::factory()->schedule('2026-07-14', '10:00', '12:00')->create();

        $data = chartData(ItHoursTrendChart::class, ['startDate' => '2026-06-01', 'endDate' => '2026-08-31']);

        expect($data['labels'])->toHaveCount(3)
            ->and($data['labels'][0])->toContain('2026')
            ->and(array_values($data['labels']))->toBe($data['labels']);
    });
});

describe('colores', function (): void {
    it('da a cada tipo un color distinto', function (): void {
        foreach (['Coro', 'Danza', 'Taller'] as $nombre) {
            $tipo = EventType::factory()->create(['name' => $nombre]);
            Event::factory()->schedule('2026-08-25', '10:00', '12:00')->create(['event_type_id' => $tipo->id]);
        }

        $colores = chartData(EventsByTypeChart::class, ['startDate' => '2026-08-01', 'endDate' => '2026-08-31'])['datasets'][0]['backgroundColor'];

        expect($colores)->toHaveCount(3)
            ->and(array_unique($colores))->toHaveCount(3);
    });

    it('no repinta un tipo cuando el ranking cambia', function (): void {
        $coro = EventType::factory()->create(['name' => 'Coro']);
        $taller = EventType::factory()->create(['name' => 'Taller']);

        // Agosto: el Coro va primero.
        Event::factory()->count(3)->schedule('2026-08-25', '10:00', '12:00')->create(['event_type_id' => $coro->id]);
        Event::factory()->schedule('2026-08-26', '10:00', '12:00')->create(['event_type_id' => $taller->id]);

        // Septiembre: se invierte el orden.
        Event::factory()->count(4)->schedule('2026-09-15', '10:00', '12:00')->create(['event_type_id' => $taller->id]);
        Event::factory()->schedule('2026-09-16', '10:00', '12:00')->create(['event_type_id' => $coro->id]);

        $colorDe = function (array $filtros) {
            $data = chartData(EventsByTypeChart::class, $filtros);

            return array_combine($data['labels'], $data['datasets'][0]['backgroundColor']);
        };

        $agosto = $colorDe(['startDate' => '2026-08-01', 'endDate' => '2026-08-31']);
        $septiembre = $colorDe(['startDate' => '2026-09-01', 'endDate' => '2026-09-30']);

        // El ranking se dio vuelta...
        expect(array_key_first($agosto))->toBe('Coro')
            ->and(array_key_first($septiembre))->toBe('Taller')
            // ...pero cada tipo conserva su color.
            ->and($septiembre['Coro'])->toBe($agosto['Coro'])
            ->and($septiembre['Taller'])->toBe($agosto['Taller']);
    });

    it('separa el total de las horas fuera de horario con colores distintos', function (): void {
        Event::factory()->schedule('2026-08-25', '09:00', '12:00', '07:00', '13:00')->create();

        $datasets = chartData(ItHoursTrendChart::class, ['startDate' => '2026-08-01', 'endDate' => '2026-08-31'])['datasets'];

        expect($datasets[0]['backgroundColor'])->toBe(ChartPalette::TOTAL)
            ->and($datasets[1]['backgroundColor'])->toBe(ChartPalette::OUTSIDE_HOURS)
            ->and($datasets[0]['backgroundColor'])->not->toBe($datasets[1]['backgroundColor']);
    });

    it('usa un color propio en la gráfica por área', function (): void {
        Event::factory()->schedule('2026-08-25', '10:00', '12:00')->create();

        $dataset = chartData(EventsByAreaChart::class, ['startDate' => '2026-08-01', 'endDate' => '2026-08-31'])['datasets'][0];

        expect($dataset['backgroundColor'])
            ->toBe(ChartPalette::BY_AREA)
            ->not->toBe(ChartPalette::TOTAL)
            ->not->toBe(ChartPalette::OUTSIDE_HOURS);
    });

    it('agrupa en Otros más allá de ocho tipos, en vez de inventar colores', function (): void {
        foreach (range(1, 10) as $i) {
            $tipo = EventType::factory()->create(['name' => "Tipo $i"]);
            Event::factory()->count(11 - $i)->schedule('2026-08-25', '10:00', '12:00')->create(['event_type_id' => $tipo->id]);
        }

        $data = chartData(EventsByTypeChart::class, ['startDate' => '2026-08-01', 'endDate' => '2026-08-31']);

        expect($data['labels'])->toHaveCount(9)
            ->and(end($data['labels']))->toBe('Otros')
            ->and(end($data['datasets'][0]['backgroundColor']))->toBe(ChartPalette::OTHER);
    });
});

describe('las gráficas responden al mismo filtro que las cifras', function (): void {
    it('se vacían al mover el rango fuera de los datos', function (): void {
        Event::factory()->schedule('2026-08-25', '10:00', '12:00')->create();

        $dentro = chartData(EventsByTypeChart::class, ['startDate' => '2026-08-01', 'endDate' => '2026-08-31']);
        $fuera = chartData(EventsByTypeChart::class, ['startDate' => '2027-01-01', 'endDate' => '2027-01-31']);

        expect($dentro['labels'])->toHaveCount(1)
            ->and($fuera['labels'])->toBe([]);
    });

    it('las tres gráficas usan el mismo rango por defecto', function (): void {
        Event::factory()->schedule(now()->subMonth()->toDateString(), '10:00', '12:00')->create();
        Event::factory()->schedule(now()->subMonths(9)->toDateString(), '10:00', '12:00')->create();

        foreach ([EventsByTypeChart::class, EventsByAreaChart::class, ItHoursTrendChart::class] as $widget) {
            expect(chartData($widget)['labels'])->toHaveCount(1);
        }
    });
});
