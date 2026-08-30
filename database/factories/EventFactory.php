<?php

namespace Database\Factories;

use App\Enums\ItPersonRole;
use App\Models\Event;
use App\Models\EventType;
use App\Models\ExternalResponsible;
use App\Models\ItPerson;
use App\Models\ItService;
use App\Models\RequestingArea;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Por defecto: un día hábil cualquiera de los últimos seis meses, evento
     * de 10:00 a 12:00 y TI de 09:30 a 12:30. Queda íntegramente dentro del
     * horario laboral, así que las pruebas parten de cero horas fuera y cada
     * escenario declara explícitamente su desviación.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $day = $this->weekday(CarbonImmutable::parse(fake()->dateTimeBetween('-6 months', 'now')));

        return [
            'title' => rtrim(fake()->unique()->sentence(3), '.'),
            'event_type_id' => EventType::factory(),
            'requesting_area_id' => RequestingArea::factory(),
            'external_responsible_id' => ExternalResponsible::factory(),
            'started_at' => $day->setTime(10, 0),
            'ended_at' => $day->setTime(12, 0),
            'it_started_at' => $day->setTime(9, 30),
            'it_ended_at' => $day->setTime(12, 30),
            'incidents' => null,
        ];
    }

    /**
     * Fija las dos ventanas de tiempo con precisión, que es lo que necesitan
     * las pruebas del motor de cálculo.
     *
     * Si no se pasa la ventana de TI, se asume el patrón habitual: TI llega
     * media hora antes y se queda media hora después.
     */
    public function schedule(
        string $date,
        string $eventStart,
        string $eventEnd,
        ?string $itStart = null,
        ?string $itEnd = null,
    ): static {
        return $this->state(function (array $attributes) use ($date, $eventStart, $eventEnd, $itStart, $itEnd): array {
            $day = CarbonImmutable::parse($date)->startOfDay();

            $startedAt = $day->setTimeFromTimeString($eventStart);
            $endedAt = $day->setTimeFromTimeString($eventEnd);

            return [
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'it_started_at' => $itStart === null
                    ? $startedAt->subMinutes(30)
                    : $day->setTimeFromTimeString($itStart),
                'it_ended_at' => $itEnd === null
                    ? $endedAt->addMinutes(30)
                    : $day->setTimeFromTimeString($itEnd),
            ];
        });
    }

    /**
     * Evento que empieza antes de la hora de entrada y termina después de la
     * de salida, desbordando el horario por ambos lados.
     */
    public function outsideWorkingHours(): static
    {
        return $this->schedule($this->nextWeekday()->toDateString(), '07:00', '18:00');
    }

    /**
     * Evento en sábado: día no laboral por defecto, así que todas sus horas
     * caen fuera del horario.
     */
    public function onSaturday(): static
    {
        $saturday = CarbonImmutable::parse('next saturday')->startOfDay();

        return $this->schedule($saturday->toDateString(), '09:00', '13:00');
    }

    public function withIncidents(string $incidents = 'Falla intermitente del micrófono inalámbrico.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'incidents' => $incidents,
        ]);
    }

    /**
     * Adjunta una persona de TI con el rol indicado. El rol viaja al pivote,
     * que es donde queda congelado.
     */
    public function withItPerson(ItPersonRole $role, ?ItPerson $person = null): static
    {
        return $this->hasAttached(
            $person ?? ItPerson::factory()->state(['role' => $role]),
            ['role' => $role],
            'itPeople',
        );
    }

    public function withItServices(int $count = 2): static
    {
        return $this->hasAttached(ItService::factory()->count($count), relationship: 'itServices');
    }

    /**
     * Corre la fecha al siguiente día hábil si cayó en fin de semana, para
     * que el escenario por defecto no arrastre un sábado por accidente.
     */
    private function weekday(CarbonImmutable $date): CarbonImmutable
    {
        return $date->isWeekend()
            ? $date->next(CarbonImmutable::MONDAY)
            : $date;
    }

    private function nextWeekday(): CarbonImmutable
    {
        return $this->weekday(CarbonImmutable::now()->startOfDay());
    }
}
