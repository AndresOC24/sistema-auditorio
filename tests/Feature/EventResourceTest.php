<?php

use App\Enums\ItPersonRole;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Models\Event;
use App\Models\EventType;
use App\Models\ExternalResponsible;
use App\Models\ItPerson;
use App\Models\ItService;
use App\Models\RequestingArea;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create(['email' => config('auditorio.admin.email')]));
});

/**
 * Datos mínimos de un evento capturado desde el panel.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function eventFormData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Observatorio Universitario de Igualdad de Género',
        'event_type_id' => EventType::factory()->create()->id,
        'requesting_area_id' => RequestingArea::factory()->create()->id,
        'external_responsible_id' => ExternalResponsible::factory()->create()->id,
        'event_date' => '2026-08-25',
        'event_start_time' => '10:00',
        'event_end_time' => '12:00',
        'itServices' => [ItService::factory()->create()->id],
        'participations' => [
            ['it_person_id' => ItPerson::factory()->becario()->create()->id, 'role' => ItPersonRole::Becario->value],
        ],
        'incidents' => null,
    ], $overrides);
}

describe('pantallas', function (): void {
    it('renderiza el listado', function (): void {
        Event::factory()->count(3)->create();

        $this->get(EventResource::getUrl('index'))->assertOk();
    });

    it('renderiza el formulario de alta', function (): void {
        $this->get(EventResource::getUrl('create'))->assertOk();
    });

    it('renderiza el formulario de edición', function (): void {
        $event = Event::factory()->withItServices(1)->withItPerson(ItPersonRole::Becario)->create();

        $this->get(EventResource::getUrl('edit', ['record' => $event]))->assertOk();
    });
});

describe('alta de un evento', function (): void {
    it('guarda el evento completo con servicios y personas', function (): void {
        $servicioA = ItService::factory()->create();
        $servicioB = ItService::factory()->create();
        $admin = ItPerson::factory()->administrativo()->create();
        $becario = ItPerson::factory()->becario()->create();

        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData([
                'itServices' => [$servicioA->id, $servicioB->id],
                'participations' => [
                    ['it_person_id' => $admin->id, 'role' => ItPersonRole::Administrativo->value],
                    ['it_person_id' => $becario->id, 'role' => ItPersonRole::Becario->value],
                ],
                'incidents' => 'Se cayó la transmisión diez minutos.',
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $event = Event::sole();

        expect($event->itServices)->toHaveCount(2)
            ->and($event->participations)->toHaveCount(2)
            ->and($event->incidents)->toBe('Se cayó la transmisión diez minutos.');
    });

    it('compone la fecha y las dos horas, y espeja la ventana de TI', function (): void {
        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData())
            ->call('create')
            ->assertHasNoFormErrors();

        $event = Event::sole();

        expect($event->started_at->format('Y-m-d H:i'))->toBe('2026-08-25 10:00')
            ->and($event->ended_at->format('Y-m-d H:i'))->toBe('2026-08-25 12:00')
            // El equipo del auditorio está instalado: el horario capturado ya
            // es el tiempo de TI, así que las dos ventanas coinciden.
            ->and($event->it_started_at->format('Y-m-d H:i'))->toBe('2026-08-25 10:00')
            ->and($event->it_ended_at->format('Y-m-d H:i'))->toBe('2026-08-25 12:00');
    });

    it('deja escritas las métricas que calcula el motor', function (): void {
        // 15:00 a 18:00: hora y media cae después de la salida (16:30).
        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData([
                'event_start_time' => '15:00',
                'event_end_time' => '18:00',
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $event = Event::sole();

        expect($event->event_duration_minutes)->toBe(180)
            ->and($event->it_duration_minutes)->toBe(180)
            ->and($event->it_outside_hours_minutes)->toBe(90)
            ->and($event->exceeds_working_hours)->toBeTrue();
    });

    it('guarda el rol tal como se capturó, aunque difiera del vigente', function (): void {
        $persona = ItPerson::factory()->becario()->create();

        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData([
                'participations' => [
                    ['it_person_id' => $persona->id, 'role' => ItPersonRole::Administrativo->value],
                ],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Event::sole()->participations->sole()->role)->toBe(ItPersonRole::Administrativo)
            ->and($persona->fresh()->role)->toBe(ItPersonRole::Becario);
    });
});

describe('nombre del evento', function (): void {
    it('guarda el nombre tal como viene del calendario', function (): void {
        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData(['title' => '1er Simposio de Cáncer Hereditario']))
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Event::sole()->title)->toBe('1er Simposio de Cáncer Hereditario');
    });

    it('lo exige', function (): void {
        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData(['title' => null]))
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    });

    it('admite dos eventos con el mismo nombre en días distintos', function (): void {
        foreach (['2026-08-25', '2026-08-27'] as $fecha) {
            Livewire::test(CreateEvent::class)
                ->fillForm(eventFormData(['title' => 'Coro', 'event_date' => $fecha]))
                ->call('create')
                ->assertHasNoFormErrors();
        }

        expect(Event::where('title', 'Coro')->count())->toBe(2);
    });

    it('lo conserva al editar', function (): void {
        $event = Event::factory()
            ->withItServices(1)
            ->withItPerson(ItPersonRole::Becario)
            ->create(['title' => 'Cuadro de honor']);

        Livewire::test(EditEvent::class, ['record' => $event->getKey()])
            ->assertFormSet(['title' => 'Cuadro de honor'])
            ->fillForm(['title' => 'Cuadro de honor 2026'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($event->fresh()->title)->toBe('Cuadro de honor 2026');
    });
});

describe('validación', function (): void {
    it('exige nombre, tipo, área, responsable y fecha', function (): void {
        Livewire::test(CreateEvent::class)
            ->fillForm([
                'title' => null,
                'event_type_id' => null,
                'requesting_area_id' => null,
                'external_responsible_id' => null,
                'event_date' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'title' => 'required',
                'event_type_id' => 'required',
                'requesting_area_id' => 'required',
                'external_responsible_id' => 'required',
                'event_date' => 'required',
            ]);
    });

    it('rechaza un evento que termina antes de empezar', function (): void {
        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData([
                'event_start_time' => '12:00',
                'event_end_time' => '10:00',
            ]))
            ->call('create')
            ->assertHasFormErrors(['event_end_time']);
    });

    it('no deja capturar la ventana de TI por separado', function (): void {
        // Aunque llegaran horas de TI distintas, el formulario las ignora: la
        // ventana de TI se deriva siempre del horario capturado.
        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData([
                'it_start_time' => '07:00',
                'it_end_time' => '20:00',
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $event = Event::sole();

        expect($event->it_started_at->format('H:i'))->toBe('10:00')
            ->and($event->it_ended_at->format('H:i'))->toBe('12:00');
    });

    it('exige al menos un servicio', function (): void {
        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData(['itServices' => []]))
            ->call('create')
            ->assertHasFormErrors(['itServices']);
    });

    it('exige al menos una persona de TI', function (): void {
        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData(['participations' => []]))
            ->call('create')
            ->assertHasFormErrors(['participations']);
    });

    it('no guarda nada cuando el formulario es inválido', function (): void {
        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData(['event_end_time' => '08:00']))
            ->call('create')
            ->assertHasFormErrors();

        expect(Event::count())->toBe(0);
    });
});

describe('edición', function (): void {
    it('descompone las ventanas de tiempo al abrir', function (): void {
        $event = Event::factory()
            ->schedule('2026-09-10', '14:00', '16:00', '13:30', '16:30')
            ->withItServices(1)
            ->withItPerson(ItPersonRole::Becario)
            ->create();

        Livewire::test(EditEvent::class, ['record' => $event->getKey()])
            ->assertFormSet([
                'event_date' => '2026-09-10',
                'event_start_time' => '14:00',
                'event_end_time' => '16:00',
            ]);
    });

    it('no corre las horas al abrir y guardar sin cambios', function (): void {
        $event = Event::factory()
            ->schedule('2026-09-10', '14:00', '16:00', '13:30', '16:30')
            ->withItServices(1)
            ->withItPerson(ItPersonRole::Becario)
            ->create();

        // Dos vueltas: si la zona horaria se aplicara mal, el desfase se
        // acumularía en cada edición.
        foreach (range(1, 2) as $ignored) {
            Livewire::test(EditEvent::class, ['record' => $event->getKey()])
                ->call('save')
                ->assertHasNoFormErrors();
        }

        $recargado = $event->fresh();

        expect($recargado->started_at->format('Y-m-d H:i'))->toBe('2026-09-10 14:00')
            ->and($recargado->ended_at->format('Y-m-d H:i'))->toBe('2026-09-10 16:00');
    });

    it('recalcula las métricas al cambiar las horas', function (): void {
        $event = Event::factory()
            ->schedule('2026-08-25', '10:00', '12:00', '09:30', '12:30')
            ->withItServices(1)
            ->withItPerson(ItPersonRole::Becario)
            ->create();

        expect($event->it_outside_hours_minutes)->toBe(0);

        Livewire::test(EditEvent::class, ['record' => $event->getKey()])
            ->fillForm(['event_end_time' => '18:00'])
            ->call('save')
            ->assertHasNoFormErrors();

        // De 16:30 a 18:00 son 90 minutos fuera de la jornada.
        expect($event->fresh()->it_outside_hours_minutes)->toBe(90);
    });
});

describe('responsable al vuelo', function (): void {
    it('crea un responsable nuevo desde el selector del evento', function (): void {
        expect(ExternalResponsible::where('name', 'Kenia Rojas')->exists())->toBeFalse();

        ExternalResponsible::create(['name' => 'Kenia Rojas']);

        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData([
                'external_responsible_id' => ExternalResponsible::firstWhere('name', 'Kenia Rojas')->id,
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Event::sole()->externalResponsible->name)->toBe('Kenia Rojas');
    });

    it('deja el responsable nuevo disponible para el siguiente evento', function (): void {
        $nuevo = ExternalResponsible::create(['name' => 'Marco Vargas']);

        Livewire::test(CreateEvent::class)
            ->fillForm(eventFormData(['external_responsible_id' => $nuevo->id]))
            ->call('create')
            ->assertHasNoFormErrors();

        expect(ExternalResponsible::active()->pluck('name'))->toContain('Marco Vargas');
    });
});
