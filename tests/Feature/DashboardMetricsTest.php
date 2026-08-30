<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\EventsOverview;
use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create(['email' => config('auditorio.admin.email')]));
});

/**
 * Escenario con cifras conocidas, calculadas a mano contra el horario por
 * defecto (8:30 a 16:30, lunes a viernes).
 *
 * - Martes 10:00–12:00, TI 09:30–12:30 → TI 180, fuera 0
 * - Martes 09:00–12:00, TI 07:00–13:00 → TI 360, fuera 90
 * - Sábado 09:00–13:00, TI 08:00–14:00 → TI 360, fuera 360
 *
 * Totales: 3 eventos · TI 900 min · promedio 300 min · fuera 450 min
 *          2 sobrepasan · 1 en día no laboral
 */
function sembrarEscenario(): void
{
    Event::factory()->schedule('2026-08-25', '10:00', '12:00', '09:30', '12:30')->create();
    Event::factory()->schedule('2026-08-25', '09:00', '12:00', '07:00', '13:00')->create();
    Event::factory()->schedule('2026-08-29', '09:00', '13:00', '08:00', '14:00')->create();
}

/**
 * @return array<int, string>
 */
function statValues(array $filters): array
{
    $widget = Livewire::test(EventsOverview::class, ['pageFilters' => $filters])->instance();

    // getStats() es protegido; se invoca con el ámbito del widget.
    $stats = (fn () => $this->getStats())->call($widget);

    return array_map(fn ($stat): string => (string) $stat->getValue(), $stats);
}

/**
 * @return array<string, string>
 */
function rangoCompleto(): array
{
    return ['startDate' => '2026-08-01', 'endDate' => '2026-08-31'];
}

it('renderiza el dashboard', function (): void {
    $this->get(Dashboard::getUrl())->assertOk();
});

it('devuelve las seis métricas del documento', function (): void {
    sembrarEscenario();

    expect(statValues(rangoCompleto()))->toBe([
        '3',            // eventos registrados
        '15 h',         // tiempo total de TI (900 min)
        '5 h',          // promedio por evento (900 / 3)
        '7 h 30 min',   // horas fuera de horario (450 min)
        '2',            // eventos que sobrepasan el horario
        '1',            // eventos en día no laboral
    ]);
});

it('cuadra con la suma directa de las columnas', function (): void {
    sembrarEscenario();

    expect((int) Event::sum('it_duration_minutes'))->toBe(900)
        ->and((int) Event::sum('it_outside_hours_minutes'))->toBe(450)
        ->and(Event::where('exceeds_working_hours', true)->count())->toBe(2)
        ->and(Event::where('on_non_working_day', true)->count())->toBe(1);
});

describe('rango de fechas', function (): void {
    it('reduce las cifras al estrechar el rango', function (): void {
        sembrarEscenario();

        // Solo el martes: quedan dos eventos, sin el sábado.
        expect(statValues(['startDate' => '2026-08-25', 'endDate' => '2026-08-25']))
            ->toBe(['2', '9 h', '4 h 30 min', '1 h 30 min', '1', '0']);
    });

    it('incluye los eventos justo en los bordes del rango', function (): void {
        Event::factory()->schedule('2026-03-01', '10:00', '12:00')->create();
        Event::factory()->schedule('2026-03-31', '10:00', '12:00')->create();
        Event::factory()->schedule('2026-02-28', '10:00', '12:00')->create();
        Event::factory()->schedule('2026-04-01', '10:00', '12:00')->create();

        expect(statValues(['startDate' => '2026-03-01', 'endDate' => '2026-03-31'])[0])->toBe('2');
    });

    it('no deja fuera un evento que empieza tarde el último día', function (): void {
        Event::factory()->schedule('2026-03-31', '18:00', '20:00')->create();

        expect(statValues(['startDate' => '2026-03-01', 'endDate' => '2026-03-31'])[0])->toBe('1');
    });

    it('muestra ceros y no truena con un rango sin eventos', function (): void {
        sembrarEscenario();

        expect(statValues(['startDate' => '2027-01-01', 'endDate' => '2027-01-31']))
            ->toBe(['0', '0 min', '—', '0 min', '0', '0']);
    });

    it('funciona sin ningún evento en la base', function (): void {
        expect(statValues(rangoCompleto())[0])->toBe('0');
    });
});

describe('rango por defecto', function (): void {
    it('cubre el último semestre cuando no se aplica filtro', function (): void {
        $dentro = Event::factory()->schedule(now()->subMonth()->toDateString(), '10:00', '12:00')->create();
        $fuera = Event::factory()->schedule(now()->subMonths(9)->toDateString(), '10:00', '12:00')->create();

        expect(statValues([])[0])->toBe('1')
            ->and($dentro->exists)->toBeTrue()
            ->and($fuera->exists)->toBeTrue();
    });

    it('no barre toda la historia', function (): void {
        Event::factory()->schedule('2020-01-15', '10:00', '12:00')->create();

        expect(statValues([])[0])->toBe('0');
    });
});

it('no refresca solo: son datos capturados a mano', function (): void {
    $widget = new EventsOverview;

    expect((fn () => $this->pollingInterval)->call($widget))->toBeNull();
});
