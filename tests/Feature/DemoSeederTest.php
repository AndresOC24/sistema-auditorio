<?php

use App\Models\Event;
use App\Models\ExternalResponsible;
use App\Models\ItPerson;
use Database\Seeders\DemoSeeder;

use function Pest\Laravel\artisan;
use function Pest\Laravel\seed;

it('siembra un semestre de eventos verosímiles', function (): void {
    seed(DemoSeeder::class);

    expect(Event::count())->toBeGreaterThan(30)
        ->and(ItPerson::count())->toBe(5);
});

it('produce la mezcla que hace útil el reporte', function (): void {
    seed(DemoSeeder::class);

    expect(Event::where('exceeds_working_hours', true)->count())->toBeGreaterThan(0)
        ->and(Event::where('exceeds_working_hours', false)->count())->toBeGreaterThan(0)
        ->and(Event::where('on_non_working_day', true)->count())->toBeGreaterThan(0);
});

it('deja todos los eventos con cobertura de TI y servicios', function (): void {
    seed(DemoSeeder::class);

    expect(Event::doesntHave('itPeople')->count())->toBe(0)
        ->and(Event::doesntHave('itServices')->count())->toBe(0);
});

it('marca los responsables sembrados para poder distinguirlos', function (): void {
    seed(DemoSeeder::class);

    expect(ExternalResponsible::where('name', 'like', '%'.DemoSeeder::RESPONSIBLE_SUFFIX)->count())
        ->toBe(ExternalResponsible::count());
});

it('mantiene todos los eventos dentro de un solo día', function (): void {
    seed(DemoSeeder::class);

    $cruzados = Event::all()->filter(
        fn (Event $event): bool => $event->started_at->toDateString() !== $event->ended_at->toDateString()
            || $event->it_started_at->toDateString() !== $event->started_at->toDateString()
            || $event->it_ended_at->toDateString() !== $event->started_at->toDateString(),
    );

    expect($cruzados)->toBeEmpty();
});

it('no siembra eventos en el futuro', function (): void {
    seed(DemoSeeder::class);

    expect(Event::where('started_at', '>', now())->count())->toBe(0);
});

it('se niega a sembrar encima de eventos capturados a mano', function (): void {
    $propio = Event::factory()->create();

    seed(DemoSeeder::class);

    expect(Event::count())->toBe(1)
        ->and(Event::sole()->id)->toBe($propio->id);
});

describe('limpieza', function (): void {
    it('retira los eventos de demostración', function (): void {
        seed(DemoSeeder::class);

        expect(Event::count())->toBeGreaterThan(0);

        artisan('auditorio:limpiar-demo')
            ->expectsConfirmation('¿Borrar '.Event::count().' eventos de demostración y 5 responsables?', 'yes')
            ->assertSuccessful();

        expect(Event::count())->toBe(0)
            ->and(ExternalResponsible::count())->toBe(0);
    });

    it('respeta los eventos capturados a mano', function (): void {
        seed(DemoSeeder::class);

        $propio = ExternalResponsible::create(['name' => 'Responsable real']);
        $evento = Event::factory()->create(['external_responsible_id' => $propio->id]);

        artisan('auditorio:limpiar-demo')->expectsConfirmation(
            '¿Borrar '.Event::where('external_responsible_id', '!=', $propio->id)->count().' eventos de demostración y 5 responsables?',
            'yes',
        )->assertSuccessful();

        expect(Event::pluck('id'))->toContain($evento->id)
            ->and(ExternalResponsible::pluck('name'))->toContain('Responsable real');
    });

    it('avisa cuando no hay nada que borrar', function (): void {
        artisan('auditorio:limpiar-demo')
            ->expectsOutputToContain('No hay datos de demostración')
            ->assertSuccessful();
    });

    it('no borra nada si se cancela', function (): void {
        seed(DemoSeeder::class);
        $antes = Event::count();

        artisan('auditorio:limpiar-demo')
            ->expectsConfirmation('¿Borrar '.$antes.' eventos de demostración y 5 responsables?', 'no')
            ->assertSuccessful();

        expect(Event::count())->toBe($antes);
    });
});
