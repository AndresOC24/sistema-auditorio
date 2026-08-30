<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\WorkScheduleSettings;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\EventTypes\EventTypeResource;
use App\Filament\Resources\ExternalResponsibles\ExternalResponsibleResource;
use App\Filament\Resources\ItPeople\ItPersonResource;
use App\Filament\Resources\ItServices\ItServiceResource;
use App\Filament\Resources\RequestingAreas\RequestingAreaResource;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create(['email' => config('auditorio.admin.email')]));
});

dataset('pantallas', [
    'reporte' => [fn (): string => Dashboard::getUrl()],
    'horario laboral' => [fn (): string => WorkScheduleSettings::getUrl()],
    'eventos' => [fn (): string => EventResource::getUrl('index')],
    'alta de evento' => [fn (): string => EventResource::getUrl('create')],
    'tipos de evento' => [fn (): string => EventTypeResource::getUrl('index')],
    'áreas solicitantes' => [fn (): string => RequestingAreaResource::getUrl('index')],
    'servicios de TI' => [fn (): string => ItServiceResource::getUrl('index')],
    'personas de TI' => [fn (): string => ItPersonResource::getUrl('index')],
    'responsables externos' => [fn (): string => ExternalResponsibleResource::getUrl('index')],
]);

describe('todas las pantallas cargan', function (): void {
    it('carga con la base vacía', function (Closure $url): void {
        $this->get($url())->assertOk();
    })->with('pantallas');

    it('carga con datos', function (Closure $url): void {
        Event::factory()->count(10)->withItServices(2)->create();

        $this->get($url())->assertOk();
    })->with('pantallas');
});

describe('rendimiento', function (): void {
    it('no dispara más consultas al crecer el número de eventos', function (): void {
        Event::factory()->count(5)->create();
        $conPocos = countQueries(fn () => $this->get(EventResource::getUrl('index')));

        Event::factory()->count(40)->create();
        $conMuchos = countQueries(fn () => $this->get(EventResource::getUrl('index')));

        expect($conMuchos)->toBeLessThanOrEqual($conPocos);
    });

    it('no dispara más consultas en el reporte al crecer los eventos', function (): void {
        Event::factory()->count(5)->create();
        $conPocos = countQueries(fn () => $this->get(Dashboard::getUrl()));

        Event::factory()->count(40)->create();
        $conMuchos = countQueries(fn () => $this->get(Dashboard::getUrl()));

        expect($conMuchos)->toBeLessThanOrEqual($conPocos);
    });

    it('no dispara una consulta por catálogo al listar', function (): void {
        Event::factory()->count(20)->create();

        expect(countQueries(fn () => $this->get(EventTypeResource::getUrl('index'))))
            ->toBeLessThan(15);
    });
});

describe('formato en español', function (): void {
    it('nombra los días en español', function (): void {
        $event = Event::factory()->schedule('2026-08-25', '10:00', '12:00')->create();

        expect($event->started_at->translatedFormat('l'))->toBe('martes');
    });

    it('nombra los meses en español', function (): void {
        expect(now()->setDate(2026, 8, 25)->translatedFormat('F'))->toBe('agosto');
    });

    it('usa español como locale del panel', function (): void {
        expect(app()->getLocale())->toBe('es');
    });
});

/**
 * Cuenta las consultas que dispara una acción.
 */
function countQueries(Closure $action): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $action();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}
