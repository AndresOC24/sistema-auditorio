<?php

namespace App\Filament\Resources\Events\Concerns;

use Carbon\CarbonImmutable;

/**
 * Traduce entre lo que se captura y lo que se guarda.
 *
 * El formulario pide una fecha y dos horas; la tabla guarda cuatro fechas con
 * hora. Componerlas aquí hace imposible el evento que cruza la medianoche.
 *
 * **Las dos ventanas se guardan iguales.** El equipo del auditorio está
 * instalado de forma permanente: no hay montaje ni desmontaje que medir aparte,
 * así que el horario que se captura ya es el tiempo que TI estuvo a cargo. Las
 * columnas de TI se conservan en la base —no cuestan nada y sí costaría
 * recuperarlas— por si algún día el auditorio vuelve a necesitar preparación
 * previa; ese día basta con volver a mostrar los dos campos en el formulario.
 */
trait ComposesEventSchedule
{
    /**
     * Formulario → columnas.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function composeSchedule(array $data): array
    {
        $day = CarbonImmutable::parse($data['event_date'])->startOfDay();

        $startedAt = $day->setTimeFromTimeString($data['event_start_time']);
        $endedAt = $day->setTimeFromTimeString($data['event_end_time']);

        $data['started_at'] = $startedAt;
        $data['ended_at'] = $endedAt;
        $data['it_started_at'] = $startedAt;
        $data['it_ended_at'] = $endedAt;

        unset(
            $data['event_date'],
            $data['event_start_time'],
            $data['event_end_time'],
        );

        return $data;
    }

    /**
     * Columnas → formulario.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function decomposeSchedule(array $data): array
    {
        $startedAt = self::inAppTimezone($data['started_at']);

        $data['event_date'] = $startedAt->toDateString();
        $data['event_start_time'] = $startedAt->format('H:i');
        $data['event_end_time'] = self::inAppTimezone($data['ended_at'])->format('H:i');

        return $data;
    }

    /**
     * Laravel serializa las fechas a UTC al pasar el registro a arreglo, así
     * que hay que devolverlas a la zona de la aplicación antes de partirlas en
     * fecha y hora. Sin esto, abrir y guardar un evento le corría las horas
     * tantas veces como se editara.
     */
    private static function inAppTimezone(mixed $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value)->setTimezone(config('app.timezone'));
    }
}
