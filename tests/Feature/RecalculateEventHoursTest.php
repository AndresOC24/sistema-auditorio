<?php

use App\Models\Event;
use App\Models\WorkSchedule;

use function Pest\Laravel\artisan;

it('avisa cuando no hay eventos registrados', function (): void {
    artisan('auditorio:recalcular-horas')
        ->expectsOutputToContain('No hay eventos registrados.')
        ->assertSuccessful();
});

it('pone al día los eventos ya registrados tras cambiar el horario', function (): void {
    $event = Event::factory()
        ->schedule('2026-08-25', '15:00', '18:00', '15:00', '18:00')
        ->create();

    expect($event->it_outside_hours_minutes)->toBe(90);

    // El horario se acorta: lo que antes caía dentro ahora queda fuera.
    WorkSchedule::current()->update(['end_time' => '15:00']);

    artisan('auditorio:recalcular-horas')->assertSuccessful();

    expect($event->fresh()->it_outside_hours_minutes)->toBe(180);
});

it('informa cuántos eventos cambiaron', function (): void {
    Event::factory()->count(3)
        ->schedule('2026-08-25', '15:00', '18:00', '15:00', '18:00')
        ->create();

    WorkSchedule::current()->update(['end_time' => '15:00']);

    artisan('auditorio:recalcular-horas')
        ->expectsOutputToContain('3 de 3 eventos actualizados.')
        ->assertSuccessful();
});

it('no toca los eventos cuyas métricas ya están al día', function (): void {
    Event::factory()->count(2)
        ->schedule('2026-08-25', '10:00', '12:00', '09:30', '12:30')
        ->create();

    artisan('auditorio:recalcular-horas')
        ->expectsOutputToContain('0 de 2 eventos actualizados.')
        ->assertSuccessful();
});

it('muestra el horario vigente al arrancar', function (): void {
    artisan('auditorio:recalcular-horas')
        ->expectsOutputToContain('08:30')
        ->expectsOutputToContain('Lunes, Martes, Miércoles, Jueves y Viernes')
        ->assertSuccessful();
});
