<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Usuario único de TI
    |--------------------------------------------------------------------------
    |
    | El sistema es operado por una sola cuenta del área de TI. Estos valores
    | alimentan al AdminUserSeeder, que es idempotente: volver a sembrar
    | actualiza la cuenta existente en lugar de crear una segunda.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'TI Auditorio'),
        'email' => env('ADMIN_EMAIL', 'ti@ejemplo.com'),
        'password' => env('ADMIN_PASSWORD', 'cambiar-esta-clave'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Horario laboral por defecto
    |--------------------------------------------------------------------------
    |
    | Solo son los valores de arranque. Una vez creado el registro en
    | work_schedules, la configuración vive en la base de datos y se edita
    | desde el panel; el cálculo de horas fuera de horario lee de ahí y nunca
    | de este archivo.
    |
    | Los días laborales van en numeración ISO-8601: lunes = 1, domingo = 7.
    |
    */

    'work_schedule' => [
        'start_time' => '08:30',
        'end_time' => '16:30',
        'working_days' => [1, 2, 3, 4, 5],
    ],

];
