<?php

use App\Enums\ItPersonRole;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Models\Event;
use App\Models\EventType;
use App\Models\ExternalResponsible;
use App\Models\ItPerson;
use App\Models\RequestingArea;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create(['email' => config('auditorio.admin.email')]));
});

it('lista todos los eventos sin filtros', function (): void {
    $eventos = Event::factory()->count(3)->create();

    Livewire::test(ListEvents::class)
        ->assertCanSeeTableRecords($eventos);
});

describe('filtro de rango de fechas', function (): void {
    it('acota al rango e incluye ambos extremos', function (): void {
        $alInicio = Event::factory()->schedule('2026-03-01', '10:00', '12:00')->create();
        $enMedio = Event::factory()->schedule('2026-03-15', '10:00', '12:00')->create();
        $alFinal = Event::factory()->schedule('2026-03-31', '10:00', '12:00')->create();
        $fueraAntes = Event::factory()->schedule('2026-02-28', '10:00', '12:00')->create();
        $fueraDespues = Event::factory()->schedule('2026-04-01', '10:00', '12:00')->create();

        Livewire::test(ListEvents::class)
            ->filterTable('rango', ['desde' => '2026-03-01', 'hasta' => '2026-03-31'])
            ->assertCanSeeTableRecords([$alInicio, $enMedio, $alFinal])
            ->assertCanNotSeeTableRecords([$fueraAntes, $fueraDespues]);
    });

    it('admite solo el extremo inicial', function (): void {
        $antiguo = Event::factory()->schedule('2026-01-10', '10:00', '12:00')->create();
        $reciente = Event::factory()->schedule('2026-06-10', '10:00', '12:00')->create();

        Livewire::test(ListEvents::class)
            ->filterTable('rango', ['desde' => '2026-05-01'])
            ->assertCanSeeTableRecords([$reciente])
            ->assertCanNotSeeTableRecords([$antiguo]);
    });

    it('admite solo el extremo final', function (): void {
        $antiguo = Event::factory()->schedule('2026-01-10', '10:00', '12:00')->create();
        $reciente = Event::factory()->schedule('2026-06-10', '10:00', '12:00')->create();

        Livewire::test(ListEvents::class)
            ->filterTable('rango', ['hasta' => '2026-05-01'])
            ->assertCanSeeTableRecords([$antiguo])
            ->assertCanNotSeeTableRecords([$reciente]);
    });

    it('no deja fuera un evento que empieza tarde el último día del rango', function (): void {
        $tardio = Event::factory()->schedule('2026-03-31', '18:00', '20:00')->create();

        Livewire::test(ListEvents::class)
            ->filterTable('rango', ['desde' => '2026-03-01', 'hasta' => '2026-03-31'])
            ->assertCanSeeTableRecords([$tardio]);
    });
});

describe('filtros de catálogo', function (): void {
    it('filtra por tipo de evento', function (): void {
        $conferencia = EventType::factory()->create(['name' => 'Conferencia']);
        $graduacion = EventType::factory()->create(['name' => 'Graduación']);

        $unaConferencia = Event::factory()->create(['event_type_id' => $conferencia->id]);
        $unaGraduacion = Event::factory()->create(['event_type_id' => $graduacion->id]);

        Livewire::test(ListEvents::class)
            ->filterTable('event_type_id', $conferencia->id)
            ->assertCanSeeTableRecords([$unaConferencia])
            ->assertCanNotSeeTableRecords([$unaGraduacion]);
    });

    it('filtra por área solicitante', function (): void {
        $rectorado = RequestingArea::factory()->create(['name' => 'Rectorado']);
        $otra = RequestingArea::factory()->create(['name' => 'Investigación']);

        $delRectorado = Event::factory()->create(['requesting_area_id' => $rectorado->id]);
        $deLaOtra = Event::factory()->create(['requesting_area_id' => $otra->id]);

        Livewire::test(ListEvents::class)
            ->filterTable('requesting_area_id', $rectorado->id)
            ->assertCanSeeTableRecords([$delRectorado])
            ->assertCanNotSeeTableRecords([$deLaOtra]);
    });
});

describe('búsqueda por nombre del evento', function (): void {
    it('encuentra el evento escribiendo parte de su nombre', function (): void {
        $simposio = Event::factory()->create(['title' => '1er Simposio de Cáncer Hereditario']);
        $coro = Event::factory()->create(['title' => 'Coro']);

        Livewire::test(ListEvents::class)
            ->searchTable('Simposio')
            ->assertCanSeeTableRecords([$simposio])
            ->assertCanNotSeeTableRecords([$coro]);
    });

    it('agrupa los eventos que se repiten cada semana', function (): void {
        $coros = collect(['2026-08-25', '2026-08-27', '2026-08-28'])
            ->map(fn (string $fecha) => Event::factory()->schedule($fecha, '11:00', '13:00')->create(['title' => 'Coro']));

        Event::factory()->create(['title' => 'Danza']);

        Livewire::test(ListEvents::class)
            ->searchTable('Coro')
            ->assertCanSeeTableRecords($coros->all())
            ->assertCountTableRecords(3);
    });
});

describe('filtro por persona de TI', function (): void {
    it('deja solo los eventos que esa persona cubrió', function (): void {
        $ana = ItPerson::factory()->administrativo()->create(['name' => 'Ana Quispe']);
        $luis = ItPerson::factory()->becario()->create(['name' => 'Luis Mamani']);

        $deAna = Event::factory()->withItPerson(ItPersonRole::Administrativo, $ana)->create();
        $deLuis = Event::factory()->withItPerson(ItPersonRole::Becario, $luis)->create();

        Livewire::test(ListEvents::class)
            ->filterTable('itPeople', $ana->id)
            ->assertCanSeeTableRecords([$deAna])
            ->assertCanNotSeeTableRecords([$deLuis]);
    });

    it('encuentra al que compartió el evento con otra persona', function (): void {
        $ana = ItPerson::factory()->administrativo()->create(['name' => 'Ana Quispe']);
        $luis = ItPerson::factory()->becario()->create(['name' => 'Luis Mamani']);

        $compartido = Event::factory()
            ->withItPerson(ItPersonRole::Administrativo, $ana)
            ->withItPerson(ItPersonRole::Becario, $luis)
            ->create();

        foreach ([$ana, $luis] as $persona) {
            Livewire::test(ListEvents::class)
                ->filterTable('itPeople', $persona->id)
                ->assertCanSeeTableRecords([$compartido]);
        }
    });

    it('no repite el evento cuando la persona lo comparte con otras', function (): void {
        $ana = ItPerson::factory()->administrativo()->create();

        Event::factory()
            ->withItPerson(ItPersonRole::Administrativo, $ana)
            ->withItPerson(ItPersonRole::Becario)
            ->withItPerson(ItPersonRole::Becario)
            ->create();

        Livewire::test(ListEvents::class)
            ->filterTable('itPeople', $ana->id)
            ->assertCountTableRecords(1);
    });

    it('sigue encontrando a una persona ya desactivada', function (): void {
        $persona = ItPerson::factory()->becario()->create();
        $evento = Event::factory()->withItPerson(ItPersonRole::Becario, $persona)->create();

        $persona->update(['is_active' => false]);

        Livewire::test(ListEvents::class)
            ->filterTable('itPeople', $persona->id)
            ->assertCanSeeTableRecords([$evento]);
    });
});

describe('filtro por responsable del evento', function (): void {
    it('deja solo los eventos de ese responsable', function (): void {
        $kenia = ExternalResponsible::create(['name' => 'Kenia Rojas']);
        $otro = ExternalResponsible::create(['name' => 'Hugo Camacho']);

        $deKenia = Event::factory()->count(2)->create(['external_responsible_id' => $kenia->id]);
        $deOtro = Event::factory()->create(['external_responsible_id' => $otro->id]);

        Livewire::test(ListEvents::class)
            ->filterTable('external_responsible_id', $kenia->id)
            ->assertCanSeeTableRecords($deKenia)
            ->assertCanNotSeeTableRecords([$deOtro]);
    });
});

describe('los filtros se combinan', function (): void {
    it('cruza persona de TI con rango de fechas', function (): void {
        $ana = ItPerson::factory()->administrativo()->create();

        $dentro = Event::factory()
            ->schedule('2026-03-15', '10:00', '12:00')
            ->withItPerson(ItPersonRole::Administrativo, $ana)
            ->create();

        $fueraDelRango = Event::factory()
            ->schedule('2026-06-15', '10:00', '12:00')
            ->withItPerson(ItPersonRole::Administrativo, $ana)
            ->create();

        Livewire::test(ListEvents::class)
            ->filterTable('itPeople', $ana->id)
            ->filterTable('rango', ['desde' => '2026-03-01', 'hasta' => '2026-03-31'])
            ->assertCanSeeTableRecords([$dentro])
            ->assertCanNotSeeTableRecords([$fueraDelRango]);
    });

    it('cruza responsable del evento con sobrepaso de horario', function (): void {
        $kenia = ExternalResponsible::create(['name' => 'Kenia Rojas']);

        $sobrepasa = Event::factory()
            ->schedule('2026-08-25', '09:00', '12:00', '07:00', '13:00')
            ->create(['external_responsible_id' => $kenia->id]);

        $dentro = Event::factory()
            ->schedule('2026-08-25', '10:00', '12:00', '09:30', '12:30')
            ->create(['external_responsible_id' => $kenia->id]);

        Livewire::test(ListEvents::class)
            ->filterTable('external_responsible_id', $kenia->id)
            ->filterTable('exceeds_working_hours', true)
            ->assertCanSeeTableRecords([$sobrepasa])
            ->assertCanNotSeeTableRecords([$dentro]);
    });
});

describe('filtros de horario', function (): void {
    it('aísla los eventos que sobrepasan el horario', function (): void {
        $dentro = Event::factory()->schedule('2026-08-25', '10:00', '12:00', '09:30', '12:30')->create();
        $fuera = Event::factory()->schedule('2026-08-25', '09:00', '12:00', '07:00', '13:00')->create();

        Livewire::test(ListEvents::class)
            ->filterTable('exceeds_working_hours', true)
            ->assertCanSeeTableRecords([$fuera])
            ->assertCanNotSeeTableRecords([$dentro]);
    });

    it('aísla los eventos en día no laboral', function (): void {
        $laboral = Event::factory()->schedule('2026-08-25', '10:00', '12:00')->create();
        $sabado = Event::factory()->schedule('2026-08-29', '10:00', '12:00')->create();

        Livewire::test(ListEvents::class)
            ->filterTable('on_non_working_day', true)
            ->assertCanSeeTableRecords([$sabado])
            ->assertCanNotSeeTableRecords([$laboral]);
    });
});

it('ordena por fecha, del más reciente al más antiguo', function (): void {
    $antiguo = Event::factory()->schedule('2026-01-10', '10:00', '12:00')->create();
    $medio = Event::factory()->schedule('2026-03-10', '10:00', '12:00')->create();
    $reciente = Event::factory()->schedule('2026-06-10', '10:00', '12:00')->create();

    Livewire::test(ListEvents::class)
        ->assertCanSeeTableRecords([$reciente, $medio, $antiguo], inOrder: true);
});
