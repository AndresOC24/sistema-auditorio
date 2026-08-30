---
paths:
  - 'app/Filament/**'
---

# Filament

## Los catálogos extienden CatalogResource, no Resource
Los cinco catálogos comparten formulario, tabla y protección de borrado en `App\Filament\Resources\CatalogResource`. Un recurso de catálogo nuevo solo declara `$model`, etiquetas e icono; lo propio va en `extraFormComponents()`, `extraTableColumns()` y `extraTableFilters()`.

- Los modelos de catálogo implementan `Contracts\Catalog` vía el trait `Concerns\IsCatalog` (`usageCount()`, `displayName()`). Sin eso, `CatalogResource` no compila con larastan.
- **Sin borrado masivo a propósito.** Borrar un catálogo en uso debe fallar con un mensaje explicativo, y en lote eso queda a medias. `guardedDeleteAction()` cancela con notificación y sugiere desactivar.
- No uses `Pest\Livewire\livewire()`: ese plugin no está instalado. Usa `Livewire\Livewire::test()`.
- Acciones de fila en tests: `callAction(TestAction::make(DeleteAction::class)->table($record))`.
