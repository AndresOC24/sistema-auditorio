<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersEventsByDateRange;
use App\Models\Event;
use App\Support\Duration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Las seis métricas del documento.
 *
 * Se leen de las columnas calculadas de events, que el motor escribió al
 * guardar cada evento. Por eso el reporte suma en SQL y no hidrata modelos.
 */
class EventsOverview extends BaseWidget
{
    use FiltersEventsByDateRange;

    /**
     * Son datos capturados a mano, no telemetría: no tiene sentido refrescar
     * solo cada cinco segundos.
     */
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        // toBase(): la fila es un agregado, no un evento. Hidratar un modelo
        // Event para leer sumas sería mentir sobre lo que devuelve la consulta.
        $totals = $this->filteredEvents()
            ->toBase()
            ->selectRaw('COUNT(*) as eventos')
            ->selectRaw('COALESCE(SUM(it_duration_minutes), 0) as minutos_ti')
            ->selectRaw('COALESCE(SUM(it_outside_hours_minutes), 0) as minutos_fuera')
            ->selectRaw('COALESCE(SUM(exceeds_working_hours), 0) as sobrepasan')
            ->selectRaw('COALESCE(SUM(on_non_working_day), 0) as no_laborales')
            ->first();

        $eventos = (int) ($totals->eventos ?? 0);
        $minutosTi = (int) ($totals->minutos_ti ?? 0);
        $minutosFuera = (int) ($totals->minutos_fuera ?? 0);
        $sobrepasan = (int) ($totals->sobrepasan ?? 0);
        $noLaborales = (int) ($totals->no_laborales ?? 0);

        return [
            Stat::make('Eventos registrados', (string) $eventos)
                ->description($this->countDescription($eventos))
                ->color('gray'),

            Stat::make('Tiempo total de TI', Duration::humanize($minutosTi))
                ->description('Tiempo que el área estuvo a cargo del auditorio')
                ->color('primary'),

            Stat::make('Promedio por evento', $this->averageLabel($minutosTi, $eventos))
                ->description('Cuánto cuesta, en tiempo, cubrir un evento')
                ->color('gray'),

            Stat::make('Horas fuera de horario', Duration::humanize($minutosFuera))
                ->description('Tiempo de TI fuera de la jornada laboral')
                ->color($minutosFuera > 0 ? 'warning' : 'gray'),

            Stat::make('Eventos que sobrepasan el horario', (string) $sobrepasan)
                ->description($this->shareLabel($sobrepasan, $eventos))
                ->color($sobrepasan > 0 ? 'warning' : 'gray'),

            Stat::make('Eventos en día no laboral', (string) $noLaborales)
                ->description($this->shareLabel($noLaborales, $eventos))
                ->color($noLaborales > 0 ? 'danger' : 'gray'),
        ];
    }

    /**
     * La primera vez que se abre el reporte no hay nada que resumir, así que
     * en lugar del rango se dice qué hacer.
     */
    private function countDescription(int $eventos): string
    {
        if ($eventos === 0 && ! Event::query()->exists()) {
            return 'Registra el primer evento para empezar a acumular datos';
        }

        return $this->rangeLabel();
    }

    private function rangeLabel(): string
    {
        return sprintf(
            'Del %s al %s',
            $this->rangeStart()->format('d/m/Y'),
            $this->rangeEnd()->format('d/m/Y'),
        );
    }

    private function averageLabel(int $minutosTi, int $eventos): string
    {
        if ($eventos === 0) {
            return '—';
        }

        return Duration::humanize((int) round($minutosTi / $eventos));
    }

    private function shareLabel(int $parte, int $total): string
    {
        if ($total === 0) {
            return 'Sin eventos en el rango';
        }

        return sprintf('%d%% de los eventos del rango', (int) round($parte / $total * 100));
    }
}
