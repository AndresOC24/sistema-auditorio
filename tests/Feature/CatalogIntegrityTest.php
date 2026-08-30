<?php

use App\Enums\ItPersonRole;
use App\Models\Event;
use App\Models\EventType;
use App\Models\ExternalResponsible;
use App\Models\ItPerson;
use App\Models\ItService;
use App\Models\RequestingArea;
use Database\Seeders\CatalogSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

use function Pest\Laravel\seed;

dataset('catálogos', [
    'tipos de evento' => [EventType::class],
    'áreas solicitantes' => [RequestingArea::class],
    'servicios de TI' => [ItService::class],
    'personas de TI' => [ItPerson::class],
    'responsables externos' => [ExternalResponsible::class],
]);

it('rechaza nombres duplicados', function (string $model): void {
    $existente = $model::factory()->create();

    $model::factory()->create(['name' => $existente->name]);
})->with('catálogos')->throws(UniqueConstraintViolationException::class);

it('crea registros activos por defecto y permite desactivarlos', function (string $model): void {
    $activo = $model::factory()->create();
    $retirado = $model::factory()->inactive()->create();

    expect($activo->is_active)->toBeTrue()
        ->and($retirado->is_active)->toBeFalse()
        ->and($model::active()->pluck('id'))
        ->toContain($activo->id)
        ->not->toContain($retirado->id);
})->with('catálogos');

it('impide borrar un tipo de evento que ya tiene eventos registrados', function (): void {
    $event = Event::factory()->create();

    $event->eventType->delete();
})->throws(QueryException::class);

it('impide borrar un área solicitante que ya tiene eventos registrados', function (): void {
    $event = Event::factory()->create();

    $event->requestingArea->delete();
})->throws(QueryException::class);

it('impide borrar un responsable externo que ya tiene eventos registrados', function (): void {
    $event = Event::factory()->create();

    $event->externalResponsible->delete();
})->throws(QueryException::class);

it('impide borrar un servicio que ya se prestó en un evento', function (): void {
    $event = Event::factory()->withItServices(1)->create();

    $event->itServices->sole()->delete();
})->throws(QueryException::class);

it('impide borrar una persona de TI que ya participó en un evento', function (): void {
    $event = Event::factory()->withItPerson(ItPersonRole::Becario)->create();

    $event->itPeople->sole()->delete();
})->throws(QueryException::class);

it('siembra los catálogos de arranque', function (): void {
    seed(CatalogSeeder::class);

    expect(EventType::count())->toBeGreaterThan(0)
        ->and(RequestingArea::count())->toBeGreaterThan(0)
        ->and(ItService::count())->toBeGreaterThan(0)
        ->and(EventType::firstWhere('name', 'Conferencia'))->not->toBeNull()
        ->and(ItService::firstWhere('name', 'Transmisión en vivo'))->not->toBeNull();
});

it('no duplica catálogos al volver a sembrar', function (): void {
    seed(CatalogSeeder::class);
    $conteo = EventType::count();

    seed(CatalogSeeder::class);

    expect(EventType::count())->toBe($conteo);
});

it('deja las personas de TI vacías porque el listado se captura a mano cada semestre', function (): void {
    seed(CatalogSeeder::class);

    expect(ItPerson::count())->toBe(0);
});
