<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersEventsByDateRange;
use App\Support\ChartPalette;
use App\Support\Duration;
use Filament\Widgets\ChartWidget;

/**
 * Qué áreas consumen tiempo de TI.
 *
 * Se ordena por horas de TI y no por número de eventos: el reporte trata de
 * tiempo invertido, y un solo congreso puede pesar más que diez reuniones.
 */
class EventsByAreaChart extends ChartWidget
{
    use FiltersEventsByDateRange;

    protected ?string $heading = 'Horas de TI por área solicitante';

    protected ?string $description = 'Las áreas que más tiempo de TI consumen, en horas.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $rows = $this->filteredEvents()
            ->join('requesting_areas', 'requesting_areas.id', '=', 'events.requesting_area_id')
            ->selectRaw('requesting_areas.name as etiqueta, SUM(it_duration_minutes) as minutos')
            ->groupBy('requesting_areas.id', 'requesting_areas.name')
            ->orderByDesc('minutos')
            ->limit(10)
            ->pluck('minutos', 'etiqueta');

        return [
            'datasets' => [[
                'label' => 'Horas de TI',
                'data' => $rows->map(fn ($minutos): float => Duration::toHours((int) $minutos))->values()->all(),
                'backgroundColor' => ChartPalette::BY_AREA,
                'borderColor' => ChartPalette::BY_AREA,
                'borderRadius' => 4,
                'maxBarThickness' => 28,
            ]],
            'labels' => $rows->keys()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['beginAtZero' => true]],
        ];
    }
}
