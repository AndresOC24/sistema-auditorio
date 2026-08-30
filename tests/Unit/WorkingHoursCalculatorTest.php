<?php

use App\Enums\Weekday;
use App\Models\WorkSchedule;
use App\Services\WorkingHoursCalculator;
use Carbon\CarbonImmutable;

/**
 * El motor de cálculo se prueba aislado y sin base de datos: recibe un
 * WorkSchedule sin guardar y dos instantes, y devuelve minutos.
 *
 * Fechas de referencia: 2026-08-25 es martes, 2026-08-29 sábado y
 * 2026-08-30 domingo.
 */
function horario(
    string $entrada = '08:30',
    string $salida = '16:30',
    ?array $dias = null,
): WorkingHoursCalculator {
    return new WorkingHoursCalculator(new WorkSchedule([
        'start_time' => $entrada,
        'end_time' => $salida,
        'working_days' => $dias ?? Weekday::weekdayValues(),
    ]));
}

function minutosFuera(WorkingHoursCalculator $calculadora, string $desde, string $hasta): int
{
    return $calculadora->minutesOutsideWorkingHours(
        CarbonImmutable::parse($desde),
        CarbonImmutable::parse($hasta),
    );
}

describe('horario por defecto de 8:30 a 16:30, lunes a viernes', function (): void {
    it('cuenta los minutos fuera de horario', function (string $desde, string $hasta, int $esperado): void {
        expect(minutosFuera(horario(), $desde, $hasta))->toBe($esperado);
    })->with([
        'íntegramente dentro' => ['2026-08-25 10:00', '2026-08-25 12:00', 0],
        'ocupa la jornada exacta' => ['2026-08-25 08:30', '2026-08-25 16:30', 0],
        'empieza antes de la entrada' => ['2026-08-25 07:00', '2026-08-25 10:00', 90],
        'termina después de la salida' => ['2026-08-25 15:00', '2026-08-25 18:00', 90],
        'desborda por ambos lados' => ['2026-08-25 07:30', '2026-08-25 18:30', 180],
        'termina justo cuando abre' => ['2026-08-25 08:00', '2026-08-25 08:30', 30],
        'empieza justo cuando cierra' => ['2026-08-25 16:30', '2026-08-25 17:00', 30],
        'entero antes de la jornada' => ['2026-08-25 06:00', '2026-08-25 07:00', 60],
        'entero después de la jornada' => ['2026-08-25 18:00', '2026-08-25 20:00', 120],
        'sábado completo' => ['2026-08-29 09:00', '2026-08-29 13:00', 240],
        'domingo completo' => ['2026-08-30 10:00', '2026-08-30 12:00', 120],
    ]);

    it('devuelve cero cuando la ventana no tiene duración', function (): void {
        expect(minutosFuera(horario(), '2026-08-25 10:00', '2026-08-25 10:00'))->toBe(0);
    });

    it('no devuelve negativos si el fin precede al inicio', function (): void {
        expect(minutosFuera(horario(), '2026-08-25 12:00', '2026-08-25 10:00'))->toBe(0);
    });
});

describe('las horas dentro y fuera suman el total', function (): void {
    it('reparte la ventana sin perder ni inventar minutos', function (string $desde, string $hasta): void {
        $calculadora = horario();
        $inicio = CarbonImmutable::parse($desde);
        $fin = CarbonImmutable::parse($hasta);

        $total = (int) $inicio->diffInMinutes($fin);

        expect(
            $calculadora->minutesOutsideWorkingHours($inicio, $fin)
            + $calculadora->minutesWithinWorkingHours($inicio, $fin)
        )->toBe($total);
    })->with([
        ['2026-08-25 07:30', '2026-08-25 18:30'],
        ['2026-08-25 10:00', '2026-08-25 12:00'],
        ['2026-08-25 06:00', '2026-08-25 07:00'],
        ['2026-08-29 09:00', '2026-08-29 13:00'],
    ]);
});

describe('el resultado depende de la configuración, no de valores fijos', function (): void {
    it('cambia con un horario de 7:00 a 15:00', function (): void {
        $evento = ['2026-08-25 15:00', '2026-08-25 18:00'];

        expect(minutosFuera(horario(), ...$evento))->toBe(90)
            ->and(minutosFuera(horario('07:00', '15:00'), ...$evento))->toBe(180);
    });

    it('deja de contar el sábado en cuanto se marca como laboral', function (): void {
        $sabado = ['2026-08-29 09:00', '2026-08-29 13:00'];

        expect(minutosFuera(horario(), ...$sabado))->toBe(240)
            ->and(minutosFuera(
                horario(dias: [...Weekday::weekdayValues(), Weekday::Saturday->value]),
                ...$sabado,
            ))->toBe(0);
    });

    it('cuenta el lunes entero si se quita de los días laborales', function (): void {
        $lunes = ['2026-08-24 10:00', '2026-08-24 12:00'];

        expect(minutosFuera(horario(), ...$lunes))->toBe(0)
            ->and(minutosFuera(horario(dias: [2, 3, 4, 5]), ...$lunes))->toBe(120);
    });

    it('respeta una jornada más larga', function (): void {
        $evento = ['2026-08-25 07:00', '2026-08-25 19:00'];

        expect(minutosFuera(horario(), ...$evento))->toBe(240)
            ->and(minutosFuera(horario('06:00', '20:00'), ...$evento))->toBe(0);
    });
});
