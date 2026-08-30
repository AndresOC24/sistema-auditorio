---
paths:
  - 'tests/**'
---

# Tests

## Los tests corren en MySQL, no en sqlite
`phpunit.xml` apunta a la base MariaDB `db_sistema_auditorio_testing` (separada de la de desarrollo, `db_sistema_auditorio`).

Se eligió MySQL sobre sqlite en memoria a propósito: el dashboard agrega horas con funciones de fecha de SQL y las columnas son enum/TIME/JSON. Con sqlite las diferencias de motor no aparecerían hasta producción.

Si la base de pruebas no existe: `CREATE DATABASE db_sistema_auditorio_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
