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
                'uuid'   => $user->uuid,
                'name'   => $user->name,
                'email'  => $user->email,
                'avatar' => $user->avatar,
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
                'uuid'   => $user->uuid,
                'name'   => $user->name,
                'email'  => $user->email,
                'avatar' => $user->avatar,
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
            'uuid'   => $request->user()->uuid,
            'name'   => $request->user()->name,
            'email'  => $request->user()->email,
            'avatar' => $request->user()->avatar,
        ]);
    }

    // Actualizar nombre, email y/o avatar
    public function updateMe(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'   => 'sometimes|required|string|max:100',
            'email'  => 'sometimes|required|email|unique:users,email,' . $user->id,
            // Avatar como data URI base64 (ej: "data:image/jpeg;base64,...")
            // nullable permite enviarlo como null para eliminarlo
            'avatar' => [
                'sometimes',
                'nullable',
                'string',
                // Validar que sea una data URI de imagen o null
                function ($attribute, $value, $fail) {
                    if ($value !== null && !preg_match('/^data:image\/(jpeg|png|gif|webp);base64,/', $value)) {
                        $fail('El avatar debe ser una imagen en formato base64 (JPEG, PNG, GIF o WEBP).');
                    }
                    // Comprobar tamaño aproximado: base64 infla ~33%, límite ~2 MB → ~2.7 MB en base64
                    if ($value !== null && strlen($value) > 2_800_000) {
                        $fail('La imagen no puede superar 2 MB.');
                    }
                },
            ],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user'    => [
                'uuid'   => $user->uuid,
                'name'   => $user->name,
                'email'  => $user->email,
                'avatar' => $user->avatar,
            ],
        ]);
    }

    // Cambiar contraseña
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

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

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Cuenta eliminada correctamente',
        ]);
    }
}