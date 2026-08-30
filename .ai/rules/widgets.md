---
paths:
  - 'app/Filament/Widgets/**'
---

# Widgets

## Todos los widgets del reporte comparten un solo rango de fechas
Cualquier widget del dashboard usa el trait `Concerns\FiltersEventsByDateRange` y consulta por `$this->filteredEvents()`. Nunca construyas el rango por tu cuenta: si un widget interpretara el filtro distinto, el reporte diría dos cifras a la vez.

- El extremo final va a `endOfDay()`. Un evento que empieza a las 18:00 del último día del rango **cuenta**.
- Los valores por defecto están en `App\Support\ReportingPeriod` (último semestre), no en el trait: PHP deprecó llamar métodos estáticos sobre un trait y la pantalla `Dashboard` también los necesita.
- Los agregados usan `->toBase()`: la fila es una suma, no un evento. Hidratar `Event` para leerla rompe larastan y miente sobre lo que devuelve la consulta.
- `$pollingInterval = null` en todos: son datos capturados a mano, no telemetría.
- Formatea duraciones con `App\Support\Duration` (`humanize()` para texto, `toHours()` para ejes de gráficas). No escribas conversiones sueltas.
