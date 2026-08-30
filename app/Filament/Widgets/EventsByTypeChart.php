<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersEventsByDateRange;
use App\Support\ChartPalette;
use Filament\Widgets\ChartWidget;

/**
 * Qué clase de eventos consume el auditorio.
 */
class EventsByTypeChart extends ChartWidget
{
    use FiltersEventsByDateRange;

    /**
     * Más allá de ocho tipos los colores se repetirían; el resto se agrupa.
     */
    private const MAX_SLICES = 8;

    protected ?string $heading = 'Eventos por tipo';

    protected ?string $description = 'Cuántos eventos de cada tipo cubrió TI en el rango.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $rows = $this->filteredEvents()
            ->toBase()
            ->join('event_types', 'event_types.id', '=', 'events.event_type_id')
            ->selectRaw('event_types.id as tipo_id, event_types.name as etiqueta, COUNT(*) as total')
            ->groupBy('event_types.id', 'event_types.name')
            ->orderByDesc('total')
            ->get();

        $visibles = $rows->take(self::MAX_SLICES);
        $resto = $rows->skip(self::MAX_SLICES);

        $labels = $visibles->pluck('etiqueta')->all();
        $data = $visibles->map(fn ($row): int => (int) $row->total)->all();

        // El color lo fija la identidad del tipo, no su posición: al cambiar el
        // rango de fechas el ranking se reordena, y si el color siguiera al
        // orden cada tipo cambiaría de color sin haber cambiado nada.
        $colors = $visibles->map(fn ($row): string => ChartPalette::forId((int) $row->tipo_id))->all();

        if ($resto->isNotEmpty()) {
            $labels[] = 'Otros';
            $data[] = (int) $resto->sum('total');
            $colors[] = ChartPalette::OTHER;
        }

        return [
            'datasets' => [[
                'label' => 'Eventos',
                'data' => $data,
                'backgroundColor' => $colors,
                'borderWidth' => 0,
            ]],
            'labels' => $labels,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            // Separación entre porciones sin depender del color de fondo, que
            // cambia con el tema del panel.
            'spacing' => 2,
            'cutout' => '58%',
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'padding' => 14],
                ],
            ],
        ];
    }
}
