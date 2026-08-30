---
paths:
  - 'database/**'
---

# Database

## Nunca correr migrate:fresh sin verificar a qué base apunta
`--env=testing` solo sirve si existe `.env.testing`; sin él Laravel cae de vuelta en `.env` y `migrate:fresh` **borra la base de desarrollo**. Ya pasó una vez y se llevó la cuenta del panel.

- `.env.testing` existe y apunta a `db_sistema_auditorio_testing`. No lo borres.
- Antes de cualquier `migrate:fresh`, confirma el destino: `php artisan tinker --env=testing --execute 'echo config("database.connections.mysql.database");'`
- La suite de Pest no necesita `migrate:fresh` manual: `phpunit.xml` + `RefreshDatabase` ya lo manejan.
