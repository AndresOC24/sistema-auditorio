<?php

namespace App\Models;

use App\Enums\ItPersonRole;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Participación de una persona de TI en un evento.
 *
 * Modelo de pivote con clave primaria propia: el repeater de Filament (fase 5)
 * necesita identificar cada renglón para saber cuáles creó, editó o borró.
 *
 * @property int $id
 * @property int $event_id
 * @property int $it_person_id
 * @property ItPersonRole $role
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['event_id', 'it_person_id', 'role'])]
class EventItPerson extends Pivot
{
    /**
     * Filament necesita rastrear cada renglón por su llave primaria.
     */
    public $incrementing = true;

    protected $table = 'event_it_person';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => ItPersonRole::class,
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<ItPerson, $this>
     */
    public function itPerson(): BelongsTo
    {
        return $this->belongsTo(ItPerson::class);
    }
}
