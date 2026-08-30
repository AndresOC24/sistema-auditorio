<?php

use App\Enums\Weekday;
use App\Exceptions\InvalidWorkSchedule;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;

describe('valores por defecto', function (): void {
    it('arranca en 8:30 a 16:30 de lunes a viernes', function (): void {
        $schedule = WorkSchedule::current();

        expect($schedule->start_time)->toStartWith('08:30')
            ->and($schedule->end_time)->toStartWith('16:30')
            ->and($schedule->working_days)->toBe([1, 2, 3, 4, 5]);
    });

    it('crea la configuración la primera vez que se consulta', function (): void {
        expect(WorkSchedule::count())->toBe(0);

        WorkSchedule::current();

        expect(WorkSchedule::count())->toBe(1);
    });

    it('describe los días laborales en español', function (): void {
        expect(WorkSchedule::current()->workingDaysLabel())
            ->toBe('Lunes, Martes, Miércoles, Jueves y Viernes');
    });

    it('calcula la duración de la jornada en minutos', function (): void {
        expect(WorkSchedule::current()->workingMinutesPerDay())->toBe(480);
    });
});

describe('registro único', function (): void {
    it('no permite crear una segunda configuración', function (): void {
        WorkSchedule::current();

        WorkSchedule::create([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'working_days' => [1, 2, 3],
        ]);
    })->throws(InvalidWorkSchedule::class);

    it('actualiza en lugar de duplicar', function (): void {
        WorkSchedule::current()->update(['start_time' => '07:00']);
        WorkSchedule::current()->update(['end_time' => '15:00']);

        expect(WorkSchedule::count())->toBe(1);
    });
});

describe('persistencia y caché', function (): void {
    it('refleja los cambios en la siguiente lectura', function (): void {
        WorkSchedule::current();

        WorkSchedule::current()->update([
            'start_time' => '07:00',
            'end_time' => '15:00',
            'working_days' => [1, 2, 3, 4, 5, 6],
        ]);

        $releida = WorkSchedule::current();

        expect($releida->start_time)->toStartWith('07:00')
            ->and($releida->end_time)->toStartWith('15:00')
            ->and($releida->working_days)->toBe([1, 2, 3, 4, 5, 6]);
    });

    it('persiste los cambios en la base de datos', function (): void {
        WorkSchedule::current()->update(['start_time' => '06:15']);

        WorkSchedule::forgetCurrent();

        expect(WorkSchedule::current()->start_time)->toStartWith('06:15');
    });

    it('devuelve la misma instancia dentro del mismo request', function (): void {
        expect(WorkSchedule::current())->toBe(WorkSchedule::current());
    });
});

describe('normalización de días', function (): void {
    it('acepta días como cadenas y los guarda como enteros', function (): void {
        WorkSchedule::current()->update(['working_days' => ['1', '3', '5']]);

        expect(WorkSchedule::current()->working_days)->toBe([1, 3, 5]);
    });

    it('ordena y quita repetidos', function (): void {
        WorkSchedule::current()->update(['working_days' => [5, 1, 5, 3, 1]]);

        expect(WorkSchedule::current()->working_days)->toBe([1, 3, 5]);
    });
});

describe('validación', function (): void {
    it('rechaza una hora de salida anterior a la de entrada', function (): void {
        WorkSchedule::current()->update([
            'start_time' => '16:30',
            'end_time' => '08:30',
        ]);
    })->throws(InvalidWorkSchedule::class);

    it('rechaza que entrada y salida sean la misma hora', function (): void {
        WorkSchedule::current()->update([
            'start_time' => '08:30',
            'end_time' => '08:30',
        ]);
    })->throws(InvalidWorkSchedule::class);

    it('rechaza quedarse sin ningún día laboral', function (): void {
        WorkSchedule::current()->update(['working_days' => []]);
    })->throws(InvalidWorkSchedule::class);

    it('rechaza un día fuera del rango ISO', function (): void {
        WorkSchedule::current()->update(['working_days' => [1, 8]]);
    })->throws(InvalidWorkSchedule::class);

    it('deja la configuración anterior intacta cuando el cambio se rechaza', function (): void {
        WorkSchedule::current();

        try {
            WorkSchedule::current()->update(['end_time' => '06:00']);
        } catch (InvalidWorkSchedule) {
            // Esperado.
        }

        WorkSchedule::forgetCurrent();

        expect(WorkSchedule::current()->end_time)->toStartWith('16:30');
    });
});

describe('consultas que usará el motor de cálculo', function (): void {
    it('reconoce los días laborales y los que no lo son', function (): void {
        $schedule = WorkSchedule::current();

        expect($schedule->isWorkingDay(CarbonImmutable::parse('2026-08-25')))->toBeTrue()
            ->and($schedule->isWorkingDay(CarbonImmutable::parse('2026-08-29')))->toBeFalse()
            ->and($schedule->isWorkingDay(CarbonImmutable::parse('2026-08-30')))->toBeFalse();
    });

    it('reconoce el sábado como laboral cuando se configura así', function (): void {
        WorkSchedule::current()->update([
            'working_days' => [...Weekday::weekdayValues(), Weekday::Saturday->value],
        ]);

        expect(WorkSchedule::current()->isWorkingDay(CarbonImmutable::parse('2026-08-29')))->toBeTrue();
    });

    it('sitúa la jornada laboral sobre una fecha concreta', function (): void {
        $schedule = WorkSchedule::current();
        $dia = CarbonImmutable::parse('2026-08-25 13:47:00');

        expect($schedule->startOn($dia)->format('Y-m-d H:i'))->toBe('2026-08-25 08:30')
            ->and($schedule->endOn($dia)->format('Y-m-d H:i'))->toBe('2026-08-25 16:30');
    });

    it('devuelve fechas inmutables', function (): void {
        $schedule = WorkSchedule::current();
        $dia = CarbonImmutable::parse('2026-08-25');

        expect($schedule->startOn($dia))->toBeInstanceOf(CarbonImmutable::class)
            ->and($dia->format('H:i'))->toBe('00:00');
    });
});
