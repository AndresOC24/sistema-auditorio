---
paths:
  - 'app/Filament/Resources/Events/**'
---

# Events

## El formulario de evento captura fecha + 4 horas, no 4 fechas-hora
El formulario pide una `event_date` y cuatro `TimePicker`; `ComposesEventSchedule` las compone en `started_at`, `ended_at`, `it_started_at` e `it_ended_at` en `mutateFormDataBefore{Create,Save}` y las descompone en `mutateFormDataBeforeFill`.

Se hace así para ahorrar tres campos al capturar y porque vuelve **estructuralmente imposible** el evento que cruza la medianoche, que el motor de cálculo no admite.

**Trampa de zona horaria:** `mutateFormDataBeforeFill` recibe el registro ya serializado, y Laravel serializa las fechas a **UTC**. Hay que devolverlas a `config('app.timezone')` antes de partirlas en fecha y hora — sin eso, cada edición corría las horas 4 h (offset de La Paz) de forma acumulativa. Lo cubre la prueba "no corre las horas al abrir y guardar sin cambios", que guarda dos veces a propósito.

## El nombre del evento y su tipo son datos distintos
`events.title` guarda el nombre tal como aparece en el calendario ("1er Simposio de Cáncer Hereditario"); `event_type_id` guarda su categoría ("simposio"). No los confundas: el nombre es libre y casi siempre único, el tipo es catálogo y se repite.

- El campo es obligatorio y de texto libre, **no** catálogo: los nombres casi nunca se repiten y un catálogo se llenaría de basura.
- Lleva `datalist()` con los últimos 50 títulos usados, para autocompletar los eventos recurrentes del calendario (Coro, Danza, Elenco de Teatro) sin volver a escribirlos.
- La columna del listado muestra el título con el tipo como descripción debajo, y es buscable.

## El formulario captura una sola ventana de horario
El equipo del auditorio está instalado de forma permanente: no hay montaje ni desmontaje que medir aparte, solo encender micrófonos y pantalla. El horario que se captura **ya es** el tiempo que TI estuvo a cargo.

- El formulario pide `event_date` + `event_start_time` + `event_end_time`. No pide la ventana de TI.
- `ComposesEventSchedule` copia esa ventana a `it_started_at` / `it_ended_at`. Las cuatro columnas siguen en la base **a propósito**: no cuestan nada y sí costaría recuperarlas. Si algún día el auditorio vuelve a necesitar preparación previa, basta con volver a mostrar dos campos.
- El motor de cálculo (`WorkingHoursCalculator`) sigue admitiendo ventanas distintas; sus pruebas unitarias las usan. Es el formulario el que las unifica, no el dominio.
- En el reporte no pongas "tiempo en auditorio" y "tiempo de TI" como métricas separadas: hoy son el mismo número.
