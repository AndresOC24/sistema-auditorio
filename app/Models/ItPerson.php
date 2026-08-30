<?php

namespace App\Models;

use App\Enums\ItPersonRole;
use App\Models\Concerns\IsCatalog;
use App\Models\Contracts\Catalog;
use Carbon\CarbonImmutable;
use Database\Factories\ItPersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Persona del área de TI que cubre eventos.
 *
 * El rol de esta tabla es el rol *vigente*. El rol con el que participó en un
 * evento concreto vive en el pivote event_it_person y no cambia al editar
 * este registro.
 *
 * @property int $id
 * @property string $name
 * @property ItPersonRole $role
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'role', 'is_active'])]
class ItPerson extends Model implements Catalog
{
    /** @use HasFactory<ItPersonFactory> */
    use HasFactory, IsCatalog;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => ItPersonRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * El listado rota cada semestre: quien deja de estar se desactiva, nunca
     * se borra, para no perder su participación en eventos pasados.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return BelongsToMany<Event, $this, EventItPerson>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->using(EventItPerson::class)
            ->withPivot('role')
            ->withTimestamps();
    }
}
