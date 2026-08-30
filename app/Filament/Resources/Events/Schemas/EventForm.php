<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\ItPersonRole;
use App\Models\Event;
use App\Models\ItPerson;
use App\Services\WorkingHoursCalculator;
use App\Support\Duration;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Formulario de captura de un evento.
 *
 * La fecha se pide una sola vez y las cuatro horas cuelgan de ella. Además de
 * ahorrar tres campos al capturar, hace estructuralmente imposible el evento
 * que cruza la medianoche, que el motor de cálculo no admite.
 *
 * Las horas se componen en started_at, ended_at, it_started_at e it_ended_at
 * al guardar; de eso se encarga el trait ComposesEventSchedule.
 */
class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Evento')
                ->schema([
                    TextInput::make('title')
                        ->label('Nombre del evento')
                        ->helperText('Cópialo del calendario, tal cual. Por ejemplo: "1er Simposio de Cáncer Hereditario".')
                        ->required()
                        ->maxLength(255)
                        ->autofocus()
                        ->datalist(self::recentTitles(...))
                        ->columnSpanFull(),

                    Select::make('event_type_id')
                        ->label('Tipo de evento')
                        ->relationship('eventType', 'name', self::onlyActiveOrSelected())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('requesting_area_id')
                        ->label('Área solicitante')
                        ->relationship('requestingArea', 'name', self::onlyActiveOrSelected())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('external_responsible_id')
                        ->label('Responsable del evento')
                        ->helperText('Persona externa a TI. Si es alguien nuevo, escribe su nombre y créalo aquí mismo.')
                        ->relationship('externalResponsible', 'name', self::onlyActiveOrSelected())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255)
                                ->unique('external_responsibles', 'name')
                                ->validationMessages([
                                    'unique' => 'Ya existe un responsable con ese nombre.',
                                ]),
                        ])
                        ->createOptionModalHeading('Nuevo responsable'),

                    DatePicker::make('event_date')
                        ->label('Fecha')
                        ->required()
                        ->default(now())
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->live()
                        ->helperText('Un evento se registra dentro de un solo día.'),
                ])
                ->columns(2),

            Section::make('Horario')
                ->description('Desde que TI se hace cargo hasta que termina. Si te quedaste quince minutos más de lo previsto, la hora de fin son esos quince minutos incluidos: es el tiempo que el área realmente invirtió.')
                ->schema([
                    TimePicker::make('event_start_time')
                        ->label('Inicio')
                        ->seconds(false)
                        ->required()
                        ->live(onBlur: true),

                    TimePicker::make('event_end_time')
                        ->label('Fin')
                        ->seconds(false)
                        ->required()
                        ->after('event_start_time')
                        ->live(onBlur: true)
                        ->validationMessages([
                            'after' => 'La hora de fin debe ser posterior a la de inicio.',
                        ]),

                    Placeholder::make('preview')
                        ->label('Resumen')
                        ->content(self::preview(...))
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Cobertura')
                ->columnSpanFull()
                ->schema([
                    Select::make('itServices')
                        ->label('Servicios prestados')
                        ->relationship('itServices', 'name', self::onlyActive())
                        ->multiple()
                        ->required()
                        ->preload()
                        ->native(false)
                        ->columnSpanFull(),

                    Repeater::make('participations')
                        ->label('Personas de TI')
                        ->relationship()
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('Agregar persona')
                        ->table([
                            Repeater\TableColumn::make('Persona')->markAsRequired(),
                            Repeater\TableColumn::make('Rol en este evento')->markAsRequired(),
                        ])
                        ->schema([
                            Select::make('it_person_id')
                                ->label('Persona')
                                ->hiddenLabel()
                                ->options(self::itPersonOptions(...))
                                ->required()
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(self::fillCurrentRole(...))
                                ->distinct()
                                ->validationMessages([
                                    'distinct' => 'Esa persona ya está en la lista.',
                                ]),

                            Select::make('role')
                                ->label('Rol')
                                ->hiddenLabel()
                                ->options(ItPersonRole::options())
                                ->required()
                                ->native(false),
                        ])
                        ->columnSpanFull()
                        ->helperText('El rol se guarda tal cual para este evento. Si la persona cambia de rol el próximo semestre, este registro no se altera.'),

                    Textarea::make('incidents')
                        ->label('Incidencias')
                        ->placeholder('Opcional: fallas, imprevistos o algo digno de recordar.')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * Títulos ya usados, para autocompletar los eventos que se repiten cada
     * semana —Coro, Danza, Elenco de Teatro— sin volver a escribirlos.
     *
     * @return array<int, string>
     */
    private static function recentTitles(): array
    {
        return Event::query()
            ->distinct()
            ->orderByDesc('started_at')
            ->limit(50)
            ->pluck('title')
            ->all();
    }

    /**
     * Adelanta el rol vigente de la persona elegida, sin impedir cambiarlo:
     * en ese evento pudo haber participado con otro.
     */
    private static function fillCurrentRole(Set $set, ?string $state): void
    {
        if ($state === null) {
            return;
        }

        $person = ItPerson::find($state);

        if ($person !== null) {
            $set('role', $person->role->value);
        }
    }

    /**
     * Lo que el motor de cálculo va a guardar, antes de guardarlo.
     */
    private static function preview(Get $get): string
    {
        $ventana = self::measure($get('event_date'), $get('event_start_time'), $get('event_end_time'));

        if ($ventana === null) {
            return 'Completa la fecha y las dos horas para ver el resumen.';
        }

        if ($ventana['total'] === 0) {
            return 'Revisa las horas: el fin debe ser posterior al inicio.';
        }

        $resumen = sprintf('%s de TI', Duration::humanize($ventana['total']));

        if ($ventana['fuera'] > 0) {
            return $resumen.sprintf(
                ' — %s fuera del horario laboral.',
                Duration::humanize($ventana['fuera']),
            );
        }

        return $resumen.' — íntegramente dentro del horario laboral.';
    }

    /**
     * Mide una ventana de tiempo, o null si todavía falta algún dato.
     *
     * @return array{total: int, fuera: int}|null
     */
    private static function measure(mixed $date, mixed $from, mixed $to): ?array
    {
        if (! is_string($date) || ! is_string($from) || ! is_string($to)) {
            return null;
        }

        $day = CarbonImmutable::parse($date)->startOfDay();
        $start = $day->setTimeFromTimeString($from);
        $end = $day->setTimeFromTimeString($to);

        if ($end->lessThanOrEqualTo($start)) {
            return ['total' => 0, 'fuera' => 0];
        }

        return [
            'total' => (int) $start->diffInMinutes($end),
            'fuera' => WorkingHoursCalculator::make()->minutesOutsideWorkingHours($start, $end),
        ];
    }

    /**
     * Personas de TI vigentes, más las ya elegidas en este evento aunque
     * hayan salido del equipo.
     *
     * @return array<int, string>
     */
    private static function itPersonOptions(Get $get): array
    {
        $state = $get('../../participations');

        $selected = is_array($state)
            ? array_values(array_filter(array_column($state, 'it_person_id')))
            : [];

        return ItPerson::query()
            ->where(fn (Builder $query) => $query
                ->where('is_active', true)
                ->orWhereIn('id', $selected))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Solo catálogo vigente, para no ofrecer opciones retiradas.
     */
    private static function onlyActive(): Closure
    {
        return fn (Builder $query): Builder => $query->where('is_active', true);
    }

    /**
     * Catálogo vigente más el que ya tenga el registro que se está editando:
     * un evento histórico debe poder guardarse aunque su catálogo se retirara.
     */
    private static function onlyActiveOrSelected(): Closure
    {
        return function (Builder $query, Select $component): Builder {
            $selected = $component->getState();

            return $query->where(fn (Builder $inner) => $inner
                ->where('is_active', true)
                ->when($selected, fn (Builder $q) => $q->orWhere('id', $selected)));
        };
    }
}
