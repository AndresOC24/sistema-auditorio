<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\EventsByAreaChart;
use App\Filament\Widgets\EventsByTypeChart;
use App\Filament\Widgets\EventsOverview;
use App\Filament\Widgets\ItHoursTrendChart;
use App\Models\Event;
use App\Support\Duration;
use App\Support\ReportingPeriod;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Reporte de uso del auditorio.
 *
 * Un único filtro de rango de fechas alimenta a todos los widgets: con un solo
 * control se cubren las vistas semanal, mensual y semestral que pedía el
 * documento, sin tres pantallas distintas.
 */
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Reporte del auditorio';

    protected static ?string $navigationLabel = 'Reporte';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Rango de fechas')
                ->description('Todas las cifras y gráficas de abajo se calculan sobre este rango. Los dos extremos se incluyen.')
                ->schema([
                    DatePicker::make('startDate')
                        ->label('Desde')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->default(ReportingPeriod::defaultStart())
                        ->maxDate(fn (): string => now()->toDateString())
                        ->live(),

                    DatePicker::make('endDate')
                        ->label('Hasta')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->default(ReportingPeriod::defaultEnd())
                        ->afterOrEqual('startDate')
                        ->live()
                        ->validationMessages([
                            'after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
                        ]),

                    Placeholder::make('resumen')
                        ->label('En este rango')
                        ->content(self::rangeSummary(...)),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ])->columns(1);
    }

    /**
     * Lo que hay dentro del rango elegido, para no tener que bajar hasta las
     * tarjetas solo para saber si el filtro dejó algo dentro.
     */
    private static function rangeSummary(Get $get): string
    {
        $desde = $get('startDate');
        $hasta = $get('endDate');

        $eventos = Event::query()
            ->whereBetween('started_at', [
                is_string($desde) && $desde !== ''
                    ? CarbonImmutable::parse($desde)->startOfDay()
                    : ReportingPeriod::defaultStart(),
                is_string($hasta) && $hasta !== ''
                    ? CarbonImmutable::parse($hasta)->endOfDay()
                    : ReportingPeriod::defaultEnd(),
            ]);

        $total = (clone $eventos)->count();

        if ($total === 0) {
            return 'Ningún evento';
        }

        return sprintf(
            '%d evento%s · %s de TI',
            $total,
            $total === 1 ? '' : 's',
            Duration::humanize((int) $eventos->sum('it_duration_minutes')),
        );
    }

    /**
     * @return array<int, class-string>
     */
    public function getWidgets(): array
    {
        return [
            EventsOverview::class,
            ItHoursTrendChart::class,
            EventsByTypeChart::class,
            EventsByAreaChart::class,
        ];
    }

    /**
     * @return int|array<string, int|null>
     */
    public function getColumns(): int|array
    {
        return 2;
    }
}
