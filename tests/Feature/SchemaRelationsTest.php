<?php

use App\Enums\ItPersonRole;
use App\Models\Event;
use App\Models\EventType;
use App\Models\ExternalResponsible;
use App\Models\ItPerson;
use App\Models\ItService;
use App\Models\RequestingArea;
use Carbon\CarbonImmutable;

it('crea un evento con todas sus relaciones desde la factory', function (): void {
    $event = Event::factory()
        ->withItServices(3)
        ->withItPerson(ItPersonRole::Administrativo)
        ->withItPerson(ItPersonRole::Becario)
        ->withIncidents()
        ->create();

    expect($event->eventType)->toBeInstanceOf(EventType::class)
        ->and($event->requestingArea)->toBeInstanceOf(RequestingArea::class)
        ->and($event->externalResponsible)->toBeInstanceOf(ExternalResponsible::class)
        ->and($event->itServices)->toHaveCount(3)
        ->and($event->itPeople)->toHaveCount(2)
        ->and($event->incidents)->not->toBeNull();
});

it('recorre las relaciones en sentido inverso', function (): void {
    $event = Event::factory()
        ->withItServices(1)
        ->withItPerson(ItPersonRole::Becario)
        ->create();

    expect($event->eventType->events->pluck('id'))->toContain($event->id)
        ->and($event->requestingArea->events->pluck('id'))->toContain($event->id)
        ->and($event->externalResponsible->events->pluck('id'))->toContain($event->id)
        ->and($event->itServices->first()->events->pluck('id'))->toContain($event->id)
        ->and($event->itPeople->first()->events->pluck('id'))->toContain($event->id);
});

it('guarda las dos ventanas de tiempo por separado', function (): void {
    $event = Event::factory()
        ->schedule('2026-09-15', '10:00', '12:00', '09:00', '13:00')
        ->create();

    expect($event->started_at->format('Y-m-d H:i'))->toBe('2026-09-15 10:00')
        ->and($event->ended_at->format('Y-m-d H:i'))->toBe('2026-09-15 12:00')
        ->and($event->it_started_at->format('Y-m-d H:i'))->toBe('2026-09-15 09:00')
        ->and($event->it_ended_at->format('Y-m-d H:i'))->toBe('2026-09-15 13:00');
});

it('devuelve las fechas como CarbonImmutable', function (): void {
    $event = Event::factory()->create();

    expect($event->started_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($event->it_started_at)->toBeInstanceOf(CarbonImmutable::class);
});

it('admite las tres combinaciones de personal de TI del documento', function (): void {
    $soloAdministrativos = Event::factory()
        ->withItPerson(ItPersonRole::Administrativo)
        ->withItPerson(ItPersonRole::Administrativo)
        ->create();

    $soloBecarios = Event::factory()
        ->withItPerson(ItPersonRole::Becario)
        ->withItPerson(ItPersonRole::Becario)
        ->create();

    $mixto = Event::factory()
        ->withItPerson(ItPersonRole::Administrativo)
        ->withItPerson(ItPersonRole::Becario)
        ->create();

    expect($soloAdministrativos->participations->pluck('role')->unique())
        ->toEqual(collect([ItPersonRole::Administrativo]))
        ->and($soloBecarios->participations->pluck('role')->unique())
        ->toEqual(collect([ItPersonRole::Becario]))
        ->and($mixto->participations->pluck('role')->unique())->toHaveCount(2);
});

it('llena las columnas calculadas al guardar', function (): void {
    // El escenario por defecto de la factory cae íntegramente dentro del
    // horario laboral: evento de 10:00 a 12:00 y TI de 09:30 a 12:30.
    $event = Event::factory()->create();

    expect($event->event_duration_minutes)->toBe(120)
        ->and($event->it_duration_minutes)->toBe(180)
        ->and($event->event_outside_hours_minutes)->toBe(0)
        ->and($event->it_outside_hours_minutes)->toBe(0)
        ->and($event->exceeds_working_hours)->toBeFalse()
        ->and($event->on_non_working_day)->toBeFalse();
});

it('filtra eventos por rango de fechas de forma inclusiva en ambos extremos', function (): void {
    $dentroAlInicio = Event::factory()->schedule('2026-03-01', '10:00', '12:00')->create();
    $enMedio = Event::factory()->schedule('2026-03-15', '10:00', '12:00')->create();
    $dentroAlFinal = Event::factory()->schedule('2026-03-31', '10:00', '12:00')->create();
    $fuera = Event::factory()->schedule('2026-04-01', '10:00', '12:00')->create();

    $encontrados = Event::startedBetween(
        CarbonImmutable::parse('2026-03-01'),
        CarbonImmutable::parse('2026-03-31'),
    )->pluck('id');

    expect($encontrados)
        ->toContain($dentroAlInicio->id, $enMedio->id, $dentroAlFinal->id)
        ->not->toContain($fuera->id);
});

it('borra las participaciones y los servicios al borrar el evento', function (): void {
    $event = Event::factory()
        ->withItServices(2)
        ->withItPerson(ItPersonRole::Becario)
        ->create();

    $eventId = $event->id;
    $event->delete();

    expect(DB::table('event_it_person')->where('event_id', $eventId)->count())->toBe(0)
        ->and(DB::table('event_it_service')->where('event_id', $eventId)->count())->toBe(0);
});

it('deja los catálogos intactos al borrar el evento', function (): void {
    $event = Event::factory()
        ->withItServices(1)
        ->withItPerson(ItPersonRole::Becario)
        ->create();

    $event->delete();

    expect(ItService::count())->toBe(1)
        ->and(ItPerson::count())->toBe(1)
        ->and(EventType::count())->toBe(1);
});
