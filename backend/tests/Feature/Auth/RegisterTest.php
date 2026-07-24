<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    // Este trait resetea la base de datos de prueba antes de CADA test.
    // Lo necesitamos porque este test sí crea un usuario de verdad,
    // y no queremos que un test deje "basura" que afecte al siguiente.
    use RefreshDatabase;

    public function test_un_usuario_puede_registrarse(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Usuario de prueba',
            'email' => 'test.' . uniqid() . '@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'token',
                'user' => ['uuid', 'name', 'email', 'avatar'],
            ]);

        // No solo miramos la respuesta: comprobamos que el usuario
        // de verdad quedó guardado en la base de datos.
        $this->assertDatabaseHas('users', [
            'name' => 'Usuario de prueba',
        ]);
    }

    public function test_no_se_puede_registrar_con_email_repetido(): void
    {
        $email = 'duplicado.' . uniqid() . '@gmail.com';

        // Registramos una vez...
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Primero',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // ...e intentamos registrar el mismo email otra vez.
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Segundo',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 422 = "Unprocessable Entity", el código que Laravel usa
        // cuando falla una validación (aquí, la regla 'unique:users').
        $response->assertStatus(422);
    }
}