---
paths:
  - 'app/Services/**'
---

# Services

## Motor de horas fuera de horario: contrato y supuestos
`WorkingHoursCalculator` resuelve por solapamiento: total de la ventana menos la parte que cae dentro de la jornada. En día no laboral no hay jornada con la que solapar, así que el total entero cuenta como fuera — no es un caso especial en el código.

- **Asume una sola jornada.** `EventObserver` rechaza eventos que cruzan la medianoche (`InvalidEventSchedule::spansMultipleDays`), por eso no itera día por día. Si algún día se admiten eventos de varios días, este supuesto se rompe.
- Se calculan **dos** ventanas: la del evento y la de TI. `exceeds_working_hours` sigue a la de **TI**, que es el tiempo que el área realmente invirtió y lo que el reporte necesita justificar.
- Todo en minutos enteros. Nunca horas decimales: el dashboard las suma sobre rangos de meses.
- Tras cambiar el horario laboral hay que correr `php artisan auditorio:recalcular-horas`; las columnas de los eventos ya guardados quedaron con el horario anterior.
