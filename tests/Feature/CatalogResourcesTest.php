<?php

use App\Enums\ItPersonRole;
use App\Filament\Resources\EventTypes\EventTypeResource;
use App\Filament\Resources\EventTypes\Pages\ListEventTypes;
use App\Filament\Resources\ExternalResponsibles\ExternalResponsibleResource;
use App\Filament\Resources\ItPeople\ItPersonResource;
use App\Filament\Resources\ItPeople\Pages\CreateItPerson;
use App\Filament\Resources\ItServices\ItServiceResource;
use App\Filament\Resources\RequestingAreas\RequestingAreaResource;
use App\Models\Event;
use App\Models\EventType;
use App\Models\ExternalResponsible;
use App\Models\ItPerson;
use App\Models\ItService;
use App\Models\RequestingArea;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create(['email' => config('auditorio.admin.email')]));
});

dataset('recursos', [
    'tipos de evento' => [EventTypeResource::class, EventType::class],
    'áreas solicitantes' => [RequestingAreaResource::class, RequestingArea::class],
    'servicios de TI' => [ItServiceResource::class, ItService::class],
    'responsables externos' => [ExternalResponsibleResource::class, ExternalResponsible::class],
    'personas de TI' => [ItPersonResource::class, ItPerson::class],
]);

describe('descripción del módulo', function (): void {
    it('todos los catálogos declaran qué se registra en ellos', function (string $resource): void {
        expect($resource::getModuleDescription())
            ->toBeString()
            ->not->toBeEmpty();
    })->with('recursos');

    it('la muestra bajo el título del listado', function (string $resource, string $model): void {
        $this->get($resource::getUrl('index'))
            ->assertOk()
            ->assertSee($resource::getModuleDescription(), escape: false);
    })->with('recursos');
});

describe('listado', function (): void {
    it('renderiza la lista de cada catálogo', function (string $resource, string $model): void {
        $model::factory()->count(3)->create();

        $this->get($resource::getUrl('index'))->assertOk();
    })->with('recursos');

    it('renderiza el formulario de alta de cada catálogo', function (string $resource): void {
        $this->get($resource::getUrl('create'))->assertOk();
    })->with('recursos');

    it('renderiza el formulario de edición de cada catálogo', function (string $resource, string $model): void {
        $record = $model::factory()->create();

        $this->get($resource::getUrl('edit', ['record' => $record]))->assertOk();
    })->with('recursos');
});

describe('alta y edición', function (): void {
    it('crea un tipo de evento desde el panel', function (): void {
        Livewire::test(EventTypeResource::getPages()['create']->getPage())
            ->fillForm(['name' => 'Simposio', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(EventType::firstWhere('name', 'Simposio'))->not->toBeNull();
    });

    it('exige el nombre', function (): void {
        Livewire::test(EventTypeResource::getPages()['create']->getPage())
            ->fillForm(['name' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    });

    it('rechaza un nombre repetido', function (): void {
        EventType::factory()->create(['name' => 'Conferencia']);

        Livewire::test(EventTypeResource::getPages()['create']->getPage())
            ->fillForm(['name' => 'Conferencia'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    });

    it('deja guardar una edición sin cambiar el nombre', function (): void {
        $tipo = EventType::factory()->create(['name' => 'Conferencia']);

        Livewire::test(EventTypeResource::getPages()['edit']->getPage(), ['record' => $tipo->getKey()])
            ->fillForm(['name' => 'Conferencia', 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($tipo->fresh()->is_active)->toBeFalse();
    });

    it('crea una persona de TI con su rol', function (): void {
        Livewire::test(CreateItPerson::class)
            ->fillForm([
                'name' => 'Ana Quispe',
                'role' => ItPersonRole::Becario->value,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(ItPerson::firstWhere('name', 'Ana Quispe')->role)->toBe(ItPersonRole::Becario);
    });

    it('exige el rol de una persona de TI', function (): void {
        Livewire::test(CreateItPerson::class)
            ->fillForm(['name' => 'Sin rol', 'role' => null])
            ->call('create')
            ->assertHasFormErrors(['role' => 'required']);
    });
});

describe('protección de borrado', function (): void {
    it('borra un catálogo que no se usa en ningún evento', function (): void {
        $tipo = EventType::factory()->create();

        Livewire::test(ListEventTypes::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($tipo));

        expect(EventType::find($tipo->id))->toBeNull();
    });

    it('no borra un catálogo que ya aparece en eventos', function (): void {
        $event = Event::factory()->create();
        $tipo = $event->eventType;

        Livewire::test(ListEventTypes::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($tipo));

        expect(EventType::find($tipo->id))->not->toBeNull()
            ->and($event->fresh()->event_type_id)->toBe($tipo->id);
    });

    it('avisa por qué no se puede borrar', function (): void {
        $event = Event::factory()->create();

        Livewire::test(ListEventTypes::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($event->eventType))
            ->assertNotified('No se puede eliminar');
    });
});

describe('catálogos desactivados', function (): void {
    it('conserva a una persona desactivada en sus eventos pasados', function (): void {
        $persona = ItPerson::factory()->becario()->create();
        $event = Event::factory()->withItPerson(ItPersonRole::Becario, $persona)->create();

        Livewire::test(ItPersonResource::getPages()['edit']->getPage(), ['record' => $persona->getKey()])
            ->fillForm(['name' => $persona->name, 'role' => $persona->role->value, 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($persona->fresh()->is_active)->toBeFalse()
            ->and($event->fresh()->itPeople()->pluck('it_people.id'))->toContain($persona->id);
    });

    it('deja fuera del listado activo a los desactivados', function (): void {
        ItPerson::factory()->count(2)->create();
        ItPerson::factory()->inactive()->create();

        expect(ItPerson::active()->count())->toBe(2)
            ->and(ItPerson::count())->toBe(3);
    });
});
