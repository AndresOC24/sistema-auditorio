<?php

/**
 * La zona horaria y el locale son cimientos del cálculo de horas fuera de
 * horario laboral. Si config/app.php vuelve a fijar 'UTC' en duro, la
 * variable APP_TIMEZONE se ignora en silencio y todos los cálculos se
 * desplazan. Estas pruebas dejan ese error a la vista.
 */
it('respeta la zona horaria configurada en el entorno', function (): void {
    expect(config('app.timezone'))->toBe(env('APP_TIMEZONE'));
});

it('no se queda en UTC', function (): void {
    expect(config('app.timezone'))->not->toBe('UTC');
});

it('genera fechas en la zona horaria de la aplicación', function (): void {
    expect(now()->getTimezone()->getName())->toBe(config('app.timezone'));
});

it('usa español como locale de la aplicación', function (): void {
    expect(config('app.locale'))->toBe('es')
        ->and(config('app.fallback_locale'))->toBe('es');
});

it('corre las pruebas contra una base de datos separada de la de desarrollo', function (): void {
    expect(config('database.connections.mysql.database'))
        ->toBe('db_sistema_auditorio_testing')
        ->not->toBe('db_sistema_auditorio');
});
