---
paths:
  - '**/*.php'
---

# General

## Las fechas son CarbonImmutable, no Carbon
AppServiceProvider llama `Date::use(CarbonImmutable::class)`, así que `now()` y los casts `datetime` devuelven `Carbon\CarbonImmutable`.

- Anota propiedades de fecha como `CarbonImmutable|null`, nunca `Illuminate\Support\Carbon` (larastan lo marca).
- Los métodos mutadores (`addHour()`, `setTime()`) devuelven una instancia nueva; hay que reasignar el resultado. Importa sobre todo en el cálculo de horas fuera de horario laboral.
