<?php

namespace App\Models;

use App\Observers\EventObserver;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Evento de auditorio cubierto por TI.
 *
 * Lleva dos ventanas de tiempo independientes: la del evento y la de TI, que
 * normalmente es más amplia porque el montaje empieza antes y el desmontaje
 * termina después.
 *
 * Las columnas *_minutes y las banderas las escribe el motor de cálculo
 * (fase 3); en esta fase existen pero se quedan en su valor por defecto.
 *
 * @property int $id
 * @property string $title
 * @property int $event_type_id
 * @property int $requesting_area_id
 * @property int $external_responsible_id
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable $ended_at
 * @property CarbonImmutable $it_started_at
 * @property CarbonImmutable $it_ended_at
 * @property string|null $incidents
 * @property int $event_duration_minutes
 * @property int $it_duration_minutes
 * @property int $event_outside_hours_minutes
 * @property int $it_outside_hours_minutes
 * @property bool $exceeds_working_hours
 * @property bool $on_non_working_day
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(EventObserver::class)]
#[Fillable([
    'title',
    'event_type_id',
    'requesting_area_id',
    'external_responsible_id',
    'started_at',
    'ended_at',
    'it_started_at',
    'it_ended_at',
    'incidents',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * Espeja los valores por defecto de la migración para que una instancia
     * recién creada exponga ceros en lugar de null antes de que el motor de
     * cálculo (fase 3) los escriba.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'event_duration_minutes' => 0,
        'it_duration_minutes' => 0,
        'event_outside_hours_minutes' => 0,
        'it_outside_hours_minutes' => 0,
        'exceeds_working_hours' => false,
        'on_non_working_day' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
            'it_started_at' => 'immutable_datetime',
            'it_ended_at' => 'immutable_datetime',
            'event_duration_minutes' => 'integer',
            'it_duration_minutes' => 'integer',
            'event_outside_hours_minutes' => 'integer',
            'it_outside_hours_minutes' => 'integer',
            'exceeds_working_hours' => 'boolean',
            'on_non_working_day' => 'boolean',
        ];
    }

    /**
     * Eventos cuyo inicio cae dentro del rango dado. Es el filtro que alimenta
     * todo el dashboard, inclusivo en ambos extremos.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function startedBetween(Builder $query, CarbonImmutable $from, CarbonImmutable $to): void
    {
        $query->whereBetween('started_at', [$from->startOfDay(), $to->endOfDay()]);
    }

    /**
     * @return BelongsTo<EventType, $this>
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * @return BelongsTo<RequestingArea, $this>
     */
    public function requestingArea(): BelongsTo
    {
        return $this->belongsTo(RequestingArea::class);
    }

    /**
     * @return BelongsTo<ExternalResponsible, $this>
     */
    public function externalResponsible(): BelongsTo
    {
        return $this->belongsTo(ExternalResponsible::class);
    }

    /**
     * @return BelongsToMany<ItService, $this>
     */
    public function itServices(): BelongsToMany
    {
        return $this->belongsToMany(ItService::class);
    }

    /**
     * @return BelongsToMany<ItPerson, $this, EventItPerson>
     */
    public function itPeople(): BelongsToMany
    {
        return $this->belongsToMany(ItPerson::class)
            ->using(EventItPerson::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Las participaciones como registros propios. El repeater de Filament
     * (fase 5) necesita un HasMany, no el BelongsToMany, para poder editar
     * el rol de cada renglón.
     *
     * @return HasMany<EventItPerson, $this>
     */
    public function participations(): HasMany
    {
        return $this->hasMany(EventItPerson::class);
    }
}
