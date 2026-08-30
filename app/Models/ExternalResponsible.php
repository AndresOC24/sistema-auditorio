<?php

namespace App\Models;

use App\Models\Concerns\IsCatalog;
use App\Models\Contracts\Catalog;
use Carbon\CarbonImmutable;
use Database\Factories\ExternalResponsibleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Responsable del evento, externo a TI.

 * Catálogo propio, separado del de personas de TI. Se alimenta al vuelo al
 * registrar un evento: se escribe un nombre nuevo y queda disponible después.
 *
 * @property int $id
 * @property string $name
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'is_active'])]
class ExternalResponsible extends Model implements Catalog
{
    /** @use HasFactory<ExternalResponsibleFactory> */
    use HasFactory, IsCatalog;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Solo los registros vigentes, para no ofrecer catálogo retirado al
     * capturar eventos nuevos.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
