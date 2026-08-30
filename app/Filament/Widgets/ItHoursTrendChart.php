<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersEventsByDateRange;
use App\Support\ChartPalette;
use App\Support\Duration;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

/**
 * Evolución del tiempo de TI mes a mes.
 *
 * Muestra dos series: el total de horas de TI y la parte que cayó fuera del
 * horario laboral. Ver ambas juntas es lo que revela si el sobrepaso es
 * constante o se concentra en ciertas épocas.
 */
class ItHoursTrendChart extends ChartWidget
{
    use FiltersEventsByDateRange;

    protected ?string $heading = 'Horas de TI por mes';

    protected ?string $description = 'Total invertido y la parte que cayó fuera del horario laboral.';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        // toBase(): cada fila es un mes agregado, no un evento.
        $rows = $this->filteredEvents()
            ->toBase()
            ->selectRaw("DATE_FORMAT(started_at, '%Y-%m') as mes")
            ->selectRaw('SUM(it_duration_minutes) as minutos_ti')
            ->selectRaw('SUM(it_outside_hours_minutes) as minutos_fuera')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Horas de TI',
                    'data' => $rows->map(fn ($row): float => Duration::toHours((int) $row->minutos_ti))->all(),
                    'backgroundColor' => ChartPalette::TOTAL,
                    'borderColor' => ChartPalette::TOTAL,
                    'borderRadius' => 4,
                    'maxBarThickness' => 44,
                ],
                [
                    'label' => 'Fuera de horario',
                    'data' => $rows->map(fn ($row): float => Duration::toHours((int) $row->minutos_fuera))->all(),
                    'backgroundColor' => ChartPalette::OUTSIDE_HOURS,
                    'borderColor' => ChartPalette::OUTSIDE_HOURS,
                    'borderRadius' => 4,
                    'maxBarThickness' => 44,
                ],
            ],
            'labels' => $rows->map(fn ($row): string => $this->monthLabel($row->mes))->all(),
        ];
    }

    /**
     * "2026-08" → "ago 2026".
     */
    private function monthLabel(string $month): string
    {
        return CarbonImmutable::createFromFormat('Y-m', $month)->translatedFormat('M Y');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'scales' => ['y' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Horas']]],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'padding' => 14],
                ],
            ],
        ];
    }
}
