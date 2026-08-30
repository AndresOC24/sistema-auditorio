<?php

use App\Filament\Pages\WorkScheduleSettings;
use App\Models\Event;
use App\Models\User;
use App\Models\WorkSchedule;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create(['email' => config('auditorio.admin.email')]));
});

it('renderiza la pantalla de horario', function (): void {
    $this->get(WorkScheduleSettings::getUrl())->assertOk();
});

it('carga los valores vigentes al abrir', function (): void {
    WorkSchedule::current()->update([
        'start_time' => '07:00',
        'end_time' => '15:00',
        'working_days' => [1, 2, 3],
    ]);

    Livewire::test(WorkScheduleSettings::class)
        ->assertFormSet(fn (array $state): array => [
            'start_time' => $state['start_time'],
            'end_time' => $state['end_time'],
            'working_days' => [1, 2, 3],
        ]);
});

it('guarda un horario nuevo', function (): void {
    Livewire::test(WorkScheduleSettings::class)
        ->fillForm([
            'start_time' => '07:30',
            'end_time' => '17:00',
            'working_days' => [1, 2, 3, 4, 5, 6],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    WorkSchedule::forgetCurrent();
    $schedule = WorkSchedule::current();

    expect($schedule->start_time)->toStartWith('07:30')
        ->and($schedule->end_time)->toStartWith('17:00')
        ->and($schedule->working_days)->toBe([1, 2, 3, 4, 5, 6]);
});

it('rechaza una hora de salida anterior a la de entrada', function (): void {
    Livewire::test(WorkScheduleSettings::class)
        ->fillForm([
            'start_time' => '16:30',
            'end_time' => '08:30',
            'working_days' => [1, 2, 3, 4, 5],
        ])
        ->call('save')
        ->assertHasFormErrors(['end_time']);
});

it('rechaza quedarse sin días laborales', function (): void {
    Livewire::test(WorkScheduleSettings::class)
        ->fillForm([
            'start_time' => '08:30',
            'end_time' => '16:30',
            'working_days' => [],
        ])
        ->call('save')
        ->assertHasFormErrors(['working_days']);
});

it('no toca la configuración guardada cuando el formulario es inválido', function (): void {
    Livewire::test(WorkScheduleSettings::class)
        ->fillForm([
            'start_time' => '16:30',
            'end_time' => '08:30',
            'working_days' => [1],
        ])
        ->call('save');

    WorkSchedule::forgetCurrent();

    expect(WorkSchedule::current()->start_time)->toStartWith('08:30')
        ->and(WorkSchedule::current()->working_days)->toBe([1, 2, 3, 4, 5]);
});

describe('recálculo de eventos ya registrados', function (): void {
    it('avisa que hay eventos con el horario anterior', function (): void {
        Event::factory()->count(2)->create();

        Livewire::test(WorkScheduleSettings::class)
            ->fillForm([
                'start_time' => '07:00',
                'end_time' => '15:00',
                'working_days' => [1, 2, 3, 4, 5],
            ])
            ->call('save')
            ->assertNotified('Horario guardado');
    });

    it('pone al día las métricas de los eventos existentes', function (): void {
        $event = Event::factory()
            ->schedule('2026-08-25', '15:00', '18:00', '15:00', '18:00')
            ->create();

        expect($event->it_outside_hours_minutes)->toBe(90);

        Livewire::test(WorkScheduleSettings::class)
            ->fillForm([
                'start_time' => '08:30',
                'end_time' => '15:00',
                'working_days' => [1, 2, 3, 4, 5],
            ])
            ->call('save')
            ->call('recalculate')
            ->assertNotified('Eventos recalculados');

        expect($event->fresh()->it_outside_hours_minutes)->toBe(180);
    });
});

it('aparece en el grupo de Configuración', function (): void {
    expect(WorkScheduleSettings::getNavigationGroup())->toBe('Configuración')
        ->and(WorkScheduleSettings::getNavigationLabel())->toBe('Horario laboral');
});
