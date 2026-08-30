<?php

namespace App\Filament\Pages;

use App\Enums\Weekday;
use App\Exceptions\InvalidWorkSchedule;
use App\Models\Event;
use App\Models\WorkSchedule;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Pantalla única de configuración del horario laboral.
 *
 * Es la fuente de verdad del cálculo de horas fuera de horario, así que al
 * guardar avisa cuántos eventos quedaron con el horario anterior y ofrece
 * recalcularlos.
 *
 * @property-read Schema $form
 */
class WorkScheduleSettings extends Page
{
    protected string $view = 'filament.pages.work-schedule-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $title = 'Horario laboral';

    protected static ?string $navigationLabel = 'Horario laboral';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $schedule = WorkSchedule::current();

        $this->form->fill([
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'working_days' => $schedule->working_days,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Jornada')
                        ->description('El cálculo de horas fuera de horario lee estos valores. Cambiarlos afecta a los eventos que se registren desde ahora.')
                        ->schema([
                            TimePicker::make('start_time')
                                ->label('Hora de entrada')
                                ->seconds(false)
                                ->required(),

                            TimePicker::make('end_time')
                                ->label('Hora de salida')
                                ->seconds(false)
                                ->required()
                                ->after('start_time')
                                ->validationMessages([
                                    'after' => 'La hora de salida debe ser posterior a la de entrada.',
                                ]),
                        ])
                        ->columns(2),

                    Section::make('Días laborales')
                        ->description('Un evento que caiga en un día no marcado cuenta entero como fuera de horario.')
                        ->schema([
                            CheckboxList::make('working_days')
                                ->hiddenLabel()
                                ->options(Weekday::options())
                                ->required()
                                ->minItems(1)
                                ->columns(3)
                                ->validationMessages([
                                    'required' => 'Debe quedar al menos un día laboral seleccionado.',
                                    'min' => 'Debe quedar al menos un día laboral seleccionado.',
                                ]),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Guardar horario')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ])->alignment(Alignment::End),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            WorkSchedule::current()->update($data);
        } catch (InvalidWorkSchedule $exception) {
            Notification::make()
                ->danger()
                ->title('No se pudo guardar')
                ->body($exception->getMessage())
                ->send();

            return;
        }

        $this->notifySaved();
    }

    /**
     * Recalcula las métricas de los eventos ya registrados con el horario
     * nuevo. Sin esto, el reporte mezcla eventos medidos con reglas distintas.
     */
    public function recalculate(): void
    {
        $affected = 0;

        Event::query()->chunkById(200, function ($events) use (&$affected): void {
            foreach ($events as $event) {
                $event->save();

                if ($event->wasChanged()) {
                    $affected++;
                }
            }
        });

        Notification::make()
            ->success()
            ->title('Eventos recalculados')
            ->body(sprintf('%d evento%s actualizado%s con el horario nuevo.',
                $affected,
                $affected === 1 ? '' : 's',
                $affected === 1 ? '' : 's',
            ))
            ->send();
    }

    private function notifySaved(): void
    {
        $notification = Notification::make()
            ->success()
            ->title('Horario guardado');

        $registrados = Event::count();

        if ($registrados > 0) {
            $notification
                ->body(sprintf(
                    'Hay %d evento%s registrado%s con el horario anterior. Recalcúlalos para que el reporte use la misma regla.',
                    $registrados,
                    $registrados === 1 ? '' : 's',
                    $registrados === 1 ? '' : 's',
                ))
                ->persistent()
                ->actions([
                    Action::make('recalculate')
                        ->label('Recalcular ahora')
                        ->button()
                        ->dispatch('recalculateEventHours'),
                ]);
        }

        $notification->send();
    }

    /**
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return ['recalculateEventHours' => 'recalculate'];
    }
}
