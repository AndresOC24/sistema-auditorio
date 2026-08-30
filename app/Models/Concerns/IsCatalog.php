<?php

namespace App\Models\Concerns;

/**
 * Implementación compartida del contrato Catalog.
 *
 * Cada modelo que la usa define su propia relación `events()`; esta parte solo
 * la consume.
 */
trait IsCatalog
{
    public function usageCount(): int
    {
        return $this->events()->count();
    }

    public function displayName(): string
    {
        return $this->name;
    }
}
