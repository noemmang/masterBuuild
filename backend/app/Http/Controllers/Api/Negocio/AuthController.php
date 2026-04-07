<?php

namespace App\Http\Controllers\Api\Negocio;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        $token = $user->createToken('masterbuild')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'uuid'  => $user->uuid,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($data)) {
            return response()->json([
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('masterbuild')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'uuid'  => $user->uuid,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'uuid'  => $request->user()->uuid,
            'name'  => $request->user()->name,
            'email' => $request->user()->email,
        ]);
    }

    // Actualizar nombre (y opcionalmente email)
    public function updateMe(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user'    => [
                'uuid'  => $user->uuid,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    // Cambiar contraseña
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        // Verificar contraseña actual
        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $user->update([
            'password' => bcrypt($data['password']),
        ]);

        // Revocar todos los tokens excepto el actual
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente',
        ]);
    }

    // Eliminar cuenta
    public function destroyMe(Request $request)
    {
        $user = $request->user();

        // Revocar todos los tokens
        $user->tokens()->delete();

        // Eliminar datos relacionados (soft delete en cascada)
        $user->delete();

        return response()->json([
            'message' => 'Cuenta eliminada correctamente',
        ]);
    }
}