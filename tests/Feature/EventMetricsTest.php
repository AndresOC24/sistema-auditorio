<?php

use App\Exceptions\InvalidEventSchedule;
use App\Models\Event;
use App\Models\WorkSchedule;

describe('el observer escribe las columnas al guardar', function (): void {
    it('calcula duraciones de las dos ventanas', function (): void {
        $event = Event::factory()
            ->schedule('2026-08-25', '10:00', '12:00', '09:00', '13:00')
            ->create();

        expect($event->event_duration_minutes)->toBe(120)
            ->and($event->it_duration_minutes)->toBe(240);
    });

    it('deja en cero un evento íntegramente dentro del horario', function (): void {
        $event = Event::factory()
            ->schedule('2026-08-25', '10:00', '12:00', '09:00', '13:00')
            ->create();

        expect($event->event_outside_hours_minutes)->toBe(0)
            ->and($event->it_outside_hours_minutes)->toBe(0)
            ->and($event->exceeds_working_hours)->toBeFalse()
            ->and($event->on_non_working_day)->toBeFalse();
    });

    it('cuenta por separado las horas fuera del evento y las de TI', function (): void {
        // El evento cabe en el horario, pero el montaje de TI empieza a las 7:00.
        $event = Event::factory()
            ->schedule('2026-08-25', '09:00', '12:00', '07:00', '13:00')
            ->create();

        expect($event->event_outside_hours_minutes)->toBe(0)
            ->and($event->it_outside_hours_minutes)->toBe(90)
            ->and($event->exceeds_working_hours)->toBeTrue();
    });

    it('marca el sábado como día no laboral y cuenta todas sus horas', function (): void {
        $event = Event::factory()
            ->schedule('2026-08-29', '09:00', '13:00', '08:00', '14:00')
            ->create();

        expect($event->on_non_working_day)->toBeTrue()
            ->and($event->event_outside_hours_minutes)->toBe(240)
            ->and($event->it_outside_hours_minutes)->toBe(360)
            ->and($event->exceeds_working_hours)->toBeTrue();
    });

    it('recalcula al editar las horas de un evento existente', function (): void {
        $event = Event::factory()
            ->schedule('2026-08-25', '10:00', '12:00', '09:30', '12:30')
            ->create();

        expect($event->it_outside_hours_minutes)->toBe(0);

        $event->update([
            'it_started_at' => $event->it_started_at->setTime(7, 0),
        ]);

        expect($event->fresh()->it_outside_hours_minutes)->toBe(90);
    });

    it('persiste las métricas, no las calcula al vuelo', function (): void {
        $event = Event::factory()
            ->schedule('2026-08-25', '07:00', '18:00', '06:30', '18:30')
            ->create();

        $enLaBase = DB::table('events')->where('id', $event->id)->first();

        expect($enLaBase->event_outside_hours_minutes)->toBe(180)
            ->and($enLaBase->it_outside_hours_minutes)->toBe(240);
    });
});

describe('la bandera de sobrepaso sigue a la ventana de TI', function (): void {
    it('se activa cuando solo TI desborda el horario', function (): void {
        $event = Event::factory()
            ->schedule('2026-08-25', '15:00', '16:00', '15:00', '17:00')
            ->create();

        expect($event->event_outside_hours_minutes)->toBe(0)
            ->and($event->it_outside_hours_minutes)->toBe(30)
            ->and($event->exceeds_working_hours)->toBeTrue();
    });
});

describe('el cálculo lee la configuración vigente', function (): void {
    it('cambia el resultado si cambia el horario laboral', function (): void {
        WorkSchedule::current()->update([
            'start_time' => '07:00',
            'end_time' => '15:00',
        ]);

        $event = Event::factory()
            ->schedule('2026-08-25', '15:00', '18:00', '15:00', '18:00')
            ->create();

        expect($event->it_outside_hours_minutes)->toBe(180);
    });

    it('deja de marcar el sábado si se configura como laboral', function (): void {
        WorkSchedule::current()->update(['working_days' => [1, 2, 3, 4, 5, 6]]);

        $event = Event::factory()
            ->schedule('2026-08-29', '09:00', '13:00', '09:00', '13:00')
            ->create();

        expect($event->on_non_working_day)->toBeFalse()
            ->and($event->it_outside_hours_minutes)->toBe(0);
    });
});

describe('validación de las ventanas de tiempo', function (): void {
    it('rechaza un evento que termina antes de empezar', function (): void {
        Event::factory()->schedule('2026-08-25', '12:00', '10:00')->create();
    })->throws(InvalidEventSchedule::class, 'La hora de fin del evento debe ser posterior');

    it('rechaza un evento sin duración', function (): void {
        Event::factory()->schedule('2026-08-25', '10:00', '10:00')->create();
    })->throws(InvalidEventSchedule::class);

    it('rechaza una ventana de TI que termina antes de empezar', function (): void {
        Event::factory()
            ->schedule('2026-08-25', '10:00', '12:00', '13:00', '09:00')
            ->create();
    })->throws(InvalidEventSchedule::class, 'La hora de fin de TI debe ser posterior');

    it('rechaza un evento que cruza la medianoche', function (): void {
        $event = Event::factory()->make();
        $event->started_at = $event->started_at->setTime(22, 0);
        $event->ended_at = $event->started_at->addHours(4);
        $event->it_started_at = $event->started_at;
        $event->it_ended_at = $event->ended_at;

        $event->save();
    })->throws(InvalidEventSchedule::class, 'debe empezar y terminar el mismo día');

    it('rechaza que la ventana de TI caiga en otro día', function (): void {
        $event = Event::factory()->make();
        $event->it_ended_at = $event->it_ended_at->addDay();

        $event->save();
    })->throws(InvalidEventSchedule::class, 'debe empezar y terminar el mismo día');

    it('no guarda nada cuando el evento se rechaza', function (): void {
        try {
            Event::factory()->schedule('2026-08-25', '12:00', '10:00')->create();
        } catch (InvalidEventSchedule) {
            // Esperado.
        }

        expect(Event::count())->toBe(0);
    });
});
