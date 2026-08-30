---
paths:
  - app/Models/User.php
  - app/Models/WorkSchedule.php
---

# Models

## Acceso al panel restringido a una sola cuenta
El sistema es de un solo usuario. `User::canAccessPanel()` compara el correo contra `config('auditorio.admin.email')` (variable `ADMIN_EMAIL`); cualquier otra cuenta recibe 403 aunque tenga credenciales válidas.

En los tests, el usuario que necesite entrar al panel debe crearse con ese correo — una `User::factory()` sin más da 403.

`AdminUserSeeder` es idempotente y solo escribe la contraseña al crear la cuenta: volver a sembrar nunca pisa una clave ya cambiada.

## El horario laboral se lee siempre por WorkSchedule::current()
Tabla de un solo registro. Nunca leas `config('auditorio.work_schedule')` para calcular: eso son solo los valores de arranque. El cálculo de horas fuera de horario debe leer `WorkSchedule::current()`.

- `current()` crea el registro con los valores por defecto si no existe, y memoriza en el **contenedor** (no en una propiedad estática) para que no se filtre entre pruebas.
- Las invariantes —salida posterior a entrada, al menos un día laboral, días ISO válidos— se validan en el evento `saving` y lanzan `InvalidWorkSchedule`. Se sostienen venga el cambio del panel, un seeder o tinker.
- Ojo: `DatabaseSeeder` usa `WithoutModelEvents`, así que al sembrar esas validaciones **no** corren.
- Días en ISO-8601: lunes = 1, domingo = 7. `Carbon::isoWeekday()`, no `dayOfWeek`.
