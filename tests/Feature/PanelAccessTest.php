<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Crea la cuenta única de TI, la que sí tiene permitido entrar al panel.
 */
function tiUser(): User
{
    return User::factory()->create([
        'email' => config('auditorio.admin.email'),
    ]);
}

it('redirige al login cuando un invitado entra al panel', function (): void {
    get('/admin')->assertRedirect('/admin/login');
});

it('deja entrar al dashboard a la cuenta de TI', function (): void {
    actingAs(tiUser())
        ->get('/admin')
        ->assertOk();
});

it('rechaza a cualquier otra cuenta aunque esté autenticada', function (): void {
    actingAs(User::factory()->create(['email' => 'otra.persona@ejemplo.com']))
        ->get('/admin')
        ->assertForbidden();
});

it('redirige la raíz del sitio al panel', function (): void {
    get('/')->assertRedirect('admin');
});

it('no expone una ruta de registro público', function (): void {
    $registrationRoutes = collect(Route::getRoutes())
        ->map(fn ($route): string => $route->uri())
        ->filter(fn (string $uri): bool => str_contains($uri, 'register'));

    expect($registrationRoutes)->toBeEmpty();
});

it('cierra sesión al usuario y lo devuelve al login', function (): void {
    actingAs(tiUser())
        ->post('/admin/logout')
        ->assertRedirect();

    expect(auth()->check())->toBeFalse();
});
