# Sistema de Registro y Reporte de Eventos de Auditorio — TI

## Contexto

El área de TI cubre eventos de auditorio (sonido, proyección, transmisiones en vivo), lo que consume tiempo e impide atender soporte en otras áreas. No hay registro sistemático de ese tiempo. Este sistema captura cada evento y expone métricas para **justificar con datos** qué eventos requieren presencia de TI y cuáles no.

- Kenia agenda los eventos en Google Calendar (fuera del alcance de este sistema).
- El registro en este sistema es **manual** (sin acceso a la cuenta de Kenia por ahora).

## Objetivo

Sistema web **interno de registro y reporte**, usado solo por TI. No lo usa ninguna otra área. Meramente informativo: sin exportación, solo visualización en pantalla.

## Alcance

- **Un solo usuario** (autenticación de Filament).
- Registro manual de eventos.
- Dashboard con métricas y gráficas filtrables por rango de fechas.
- No exporta (ni PDF ni Excel) por ahora.

## Modelo de datos

### Evento
- **Tipo de evento** → catálogo editable.
- **Área solicitante** → catálogo editable.
- **Responsable del evento** → persona externa a TI. Se escribe el nombre; si es nuevo se guarda al vuelo y reaparece en la lista después. Catálogo propio, separado del de personas de TI.
- **Hora inicio y fin del evento** → el sistema calcula la **duración del evento**.
- **Tiempo de TI involucrado** (inicio/fin propios) → separado del tiempo de evento (TI suele llegar antes a montar y quedarse a desmontar).
- **Servicios prestados por TI** → catálogo editable, selección múltiple.
- **Personas de TI participantes** → varias por evento, cada una con rol (administrativo / becario). Combinaciones posibles: admin + becarios, solo becarios, solo administrativos.
- **Incidencias** → texto libre, opcional.
- **Horas fuera de horario laboral** → cálculo automático a **nivel de evento** (no por persona).

### Catálogos editables desde la app
- Tipos de evento
- Áreas solicitantes
- Servicios de TI
- Personas de TI (con rol admin/becario) — el listado cambia por semestre, debe ser editable.
- Responsables externos (se alimenta al vuelo al registrar eventos)

## Reglas de horario

- Horario laboral por defecto: **8:30 a 16:30, lunes a viernes**. No se trabaja sábados.
- **Configurable desde un panel**: hora de entrada, hora de salida y días laborales. El cálculo de horas fuera de horario lee de esta configuración, no de valores fijos.
- Un evento sobrepasa el horario si: empieza antes de la hora de entrada, termina después de la de salida, o cae en día no laboral.
- El cálculo cuenta **cuántas horas del evento caen fuera del horario** (no solo un sí/no).
- Nota de negocio: el sobrepaso de horario aplica a administrativos, no a becarios. (El cálculo de horas es a nivel de evento; esta distinción es para interpretación del reporte.)

## Dashboard

- **Filtro por rango de fechas libre** (cubre vistas semanal, mensual y semestral).
- Métricas:
  - Tiempo total en auditorio.
  - Tiempo total de TI.
  - Número de eventos.
  - Eventos que sobrepasan el horario laboral.
  - Eventos en sábado / día no laboral.
  - Horas fuera de horario acumuladas.
- Gráficas de apoyo, solo en pantalla.

## Decisiones explícitas (para no reabrir)

- **No** hay campo "¿TI era necesario?". Esa conclusión la saca TI manualmente a partir de los datos.
- Horas fuera de horario a **nivel de evento**, no por persona.
- Integración con Google Calendar **fuera de alcance** por ahora.
- Sin exportación por ahora.
- Un solo usuario por ahora.

## Stack

- **Laravel** + **FilamentPHP**.
- Panel admin de Filament con autenticación de un solo usuario.
- Catálogos y eventos como recursos CRUD.
- Dashboard con widgets de Filament.
- Responsable externo: campo con creación al vuelo (`createOptionForm` / `getSearchResultsUsing`).

## Orden sugerido de implementación

1. Modelos y migraciones.
2. Catálogos (tipos, áreas, servicios, personas de TI, responsables) + configuración de horario.
3. Recurso de Evento con los cálculos (duración, tiempo TI, horas fuera de horario).
4. Widgets del dashboard con filtro de rango de fechas.
