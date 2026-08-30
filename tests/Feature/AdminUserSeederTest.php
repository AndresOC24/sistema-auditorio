<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

it('siembra el usuario único de TI con los datos de configuración', function (): void {
    seed(AdminUserSeeder::class);

    $user = User::firstWhere('email', config('auditorio.admin.email'));

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe(config('auditorio.admin.name'))
        ->and(Hash::check(config('auditorio.admin.password'), $user->password))->toBeTrue();
});

it('no duplica la cuenta al volver a sembrar', function (): void {
    seed(AdminUserSeeder::class);
    seed(AdminUserSeeder::class);

    expect(User::count())->toBe(1);
});

it('no sobrescribe la contraseña de una cuenta que ya existe', function (): void {
    User::factory()->create([
        'email' => config('auditorio.admin.email'),
        'password' => 'clave-que-el-operador-ya-cambio',
    ]);

    seed(AdminUserSeeder::class);

    expect(Hash::check('clave-que-el-operador-ya-cambio', User::sole()->password))->toBeTrue();
});

it('deja al usuario sembrado en condiciones de entrar al panel', function (): void {
    seed(AdminUserSeeder::class);

    actingAs(User::sole())
        ->get('/admin')
        ->assertOk();
});
