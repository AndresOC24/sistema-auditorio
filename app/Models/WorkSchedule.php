<?php

namespace App\Models;

use App\Enums\Weekday;
use App\Exceptions\InvalidWorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Horario laboral del área de TI. Tabla de un solo registro.
 *
 * Es la fuente de verdad del cálculo de horas fuera de horario: el motor lee
 * de aquí y nunca de constantes, para que cambiar el horario sea un dato.
 *
 * @property int $id
 * @property string $start_time
 * @property string $end_time
 * @property array<int, int> $working_days
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['start_time', 'end_time', 'working_days'])]
class WorkSchedule extends Model
{
    /**
     * Se memoriza en el contenedor y no en una propiedad estática: el
     * contenedor se reconstruye entre pruebas, así que la configuración de
     * una prueba no se filtra a la siguiente.
     */
    private const CACHE_KEY = 'auditorio.work_schedule';

    /**
     * La configuración vigente, creándola con los valores por defecto la
     * primera vez que se consulta.
     */
    public static function current(): self
    {
        if (! app()->bound(self::CACHE_KEY)) {
            app()->instance(self::CACHE_KEY, self::query()->first() ?? self::createDefault());
        }

        return app()->make(self::CACHE_KEY);
    }

    /**
     * Descarta la copia memorizada. Se dispara sola al guardar; solo hace
     * falta llamarla a mano si se escribe en la tabla sin pasar por Eloquent.
     */
    public static function forgetCurrent(): void
    {
        app()->forgetInstance(self::CACHE_KEY);
    }

    public static function createDefault(): self
    {
        return self::create([
            'start_time' => config('auditorio.work_schedule.start_time'),
            'end_time' => config('auditorio.work_schedule.end_time'),
            'working_days' => config('auditorio.work_schedule.working_days'),
        ]);
    }

    /**
     * ¿Se trabaja ese día de la semana?
     */
    public function isWorkingDay(CarbonImmutable $date): bool
    {
        return in_array($date->isoWeekday(), $this->working_days, true);
    }

    /**
     * Inicio de la jornada laboral en la fecha dada.
     */
    public function startOn(CarbonImmutable $date): CarbonImmutable
    {
        return $date->startOfDay()->setTimeFromTimeString($this->start_time);
    }

    /**
     * Fin de la jornada laboral en la fecha dada.
     */
    public function endOn(CarbonImmutable $date): CarbonImmutable
    {
        return $date->startOfDay()->setTimeFromTimeString($this->end_time);
    }

    /**
     * Duración de la jornada laboral, en minutos.
     */
    public function workingMinutesPerDay(): int
    {
        $reference = CarbonImmutable::now();

        return (int) $this->startOn($reference)->diffInMinutes($this->endOn($reference));
    }

    /**
     * Los días laborales como enums, para mostrarlos con su nombre.
     *
     * @return Collection<int, Weekday>
     */
    public function workingWeekdays(): Collection
    {
        return collect($this->working_days)
            ->map(fn (int $day): Weekday => Weekday::from($day));
    }

    /**
     * Texto legible de los días laborales, para el panel y el reporte.
     */
    public function workingDaysLabel(): string
    {
        return $this->workingWeekdays()
            ->map(fn (Weekday $day): string => $day->label())
            ->join(', ', ' y ');
    }

    /**
     * Los días se guardan siempre como enteros ISO ordenados y sin repetir,
     * vengan del panel como cadenas o de un seeder como enteros.
     *
     * @return Attribute<array<int, int>, string>
     */
    protected function workingDays(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): array => array_map(
                fn (mixed $day): int => (int) $day,
                json_decode($value, true),
            ),
            set: fn (array $value): string => (string) json_encode(
                collect($value)
                    ->map(fn (mixed $day): int => (int) $day)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
            ),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'string',
            'end_time' => 'string',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (self $schedule): void {
            if (self::query()->exists()) {
                throw new InvalidWorkSchedule(
                    'Ya existe una configuración de horario. Edita la que hay en lugar de crear otra.',
                );
            }
        });

        self::saving(fn (self $schedule) => $schedule->guardAgainstInvalidState());

        self::saved(fn () => self::forgetCurrent());
        self::deleted(fn () => self::forgetCurrent());
    }

    /**
     * Invariantes que el motor de cálculo da por sentadas.
     *
     * @throws InvalidWorkSchedule
     */
    private function guardAgainstInvalidState(): void
    {
        foreach ($this->working_days as $day) {
            if (Weekday::tryFrom($day) === null) {
                throw InvalidWorkSchedule::unknownWeekday($day);
            }
        }

        if ($this->working_days === []) {
            throw InvalidWorkSchedule::withoutWorkingDays();
        }

        $reference = CarbonImmutable::now();

        if ($this->endOn($reference)->lessThanOrEqualTo($this->startOn($reference))) {
            throw InvalidWorkSchedule::endBeforeStart($this->start_time, $this->end_time);
        }
    }
}
