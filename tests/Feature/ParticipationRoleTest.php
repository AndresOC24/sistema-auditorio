<?php

use App\Enums\ItPersonRole;
use App\Models\Event;
use App\Models\ItPerson;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * El listado de personas de TI rota cada semestre y un becario puede pasar a
 * administrativo. Estas pruebas fijan que el reporte histórico no se reescriba
 * cuando eso ocurre.
 */
it('congela en el pivote el rol con el que participó la persona', function (): void {
    $persona = ItPerson::factory()->becario()->create();

    $event = Event::factory()
        ->withItPerson(ItPersonRole::Becario, $persona)
        ->create();

    expect($event->participations->sole()->role)->toBe(ItPersonRole::Becario);
});

it('no altera eventos pasados al promover a la persona en el catálogo', function (): void {
    $persona = ItPerson::factory()->becario()->create();

    $eventoPasado = Event::factory()
        ->withItPerson(ItPersonRole::Becario, $persona)
        ->create();

    $persona->update(['role' => ItPersonRole::Administrativo]);

    expect($eventoPasado->participations()->sole()->role)
        ->toBe(ItPersonRole::Becario)
        ->and($persona->fresh()->role)
        ->toBe(ItPersonRole::Administrativo);
});

it('permite que la misma persona participe con roles distintos en eventos distintos', function (): void {
    $persona = ItPerson::factory()->becario()->create();

    $comoBecario = Event::factory()
        ->withItPerson(ItPersonRole::Becario, $persona)
        ->create();

    $comoAdministrativo = Event::factory()
        ->withItPerson(ItPersonRole::Administrativo, $persona)
        ->create();

    expect($comoBecario->participations()->sole()->role)->toBe(ItPersonRole::Becario)
        ->and($comoAdministrativo->participations()->sole()->role)->toBe(ItPersonRole::Administrativo);
});

it('impide registrar dos veces a la misma persona en un evento', function (): void {
    $persona = ItPerson::factory()->create();
    $event = Event::factory()->withItPerson(ItPersonRole::Becario, $persona)->create();

    $event->itPeople()->attach($persona, ['role' => ItPersonRole::Administrativo]);
})->throws(UniqueConstraintViolationException::class);

it('conserva a una persona desactivada dentro de sus eventos históricos', function (): void {
    $persona = ItPerson::factory()->becario()->create();
    $event = Event::factory()->withItPerson(ItPersonRole::Becario, $persona)->create();

    $persona->update(['is_active' => false]);

    expect($event->itPeople()->pluck('it_people.id'))->toContain($persona->id)
        ->and(ItPerson::active()->count())->toBe(0);
});
