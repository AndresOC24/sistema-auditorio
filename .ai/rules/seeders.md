---
paths:
  - 'database/seeders/**'
---

# Seeders

## DemoSeeder no se llama desde DatabaseSeeder
`DatabaseSeeder` solo siembra lo indispensable: cuenta de TI, catálogos y horario. `DemoSeeder` queda fuera a propósito — sus datos son inventados y no deben mezclarse con el registro real.

- Sembrar: `php artisan db:seed --class=DemoSeeder` (se niega en producción).
- Quitar: `php artisan auditorio:limpiar-demo`. Distingue lo sembrado por el sufijo `(demo)` en el nombre del responsable externo (`DemoSeeder::RESPONSIBLE_SUFFIX`), así que los eventos capturados a mano sobreviven.
- El seeder nunca crea eventos futuros ni que crucen la medianoche: esto es un registro de lo ocurrido, no una agenda. Hay pruebas que lo fijan.
- Las personas de TI **no** se siembran en `CatalogSeeder` (el listado rota cada semestre y se captura a mano); `DemoSeeder` sí crea cinco.
