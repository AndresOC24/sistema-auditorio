<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\Event;
use App\Support\ReportingPeriod;
use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

/**
 * Rango de fechas del dashboard, compartido por todos los widgets.
 *
 * Vive en un solo sitio para que las cifras y las gráficas no puedan
 * discrepar: si un widget interpretara el rango distinto, el reporte diría dos
 * cosas a la vez y nadie sabría cuál creer.
 */
trait FiltersEventsByDateRange
{
    use InteractsWithPageFilters;

    /**
     * Eventos dentro del rango elegido, inclusivo en ambos extremos.
     *
     * El extremo final llega hasta el fin del día: un evento que empieza a las
     * 18:00 del último día del rango cuenta.
     *
     * @return Builder<Event>
     */
    protected function filteredEvents(): Builder
    {
        return Event::query()
            ->whereBetween('started_at', [$this->rangeStart(), $this->rangeEnd()]);
    }

    protected function rangeStart(): CarbonImmutable
    {
        $value = $this->pageFilters['startDate'] ?? null;

        return is_string($value) && $value !== ''
            ? CarbonImmutable::parse($value)->startOfDay()
            : ReportingPeriod::defaultStart();
    }

    protected function rangeEnd(): CarbonImmutable
    {
        $value = $this->pageFilters['endDate'] ?? null;

        return is_string($value) && $value !== ''
            ? CarbonImmutable::parse($value)->endOfDay()
            : ReportingPeriod::defaultEnd();
    }
}
