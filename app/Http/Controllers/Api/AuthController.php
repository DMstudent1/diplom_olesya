<?php

namespace App\Http\Controllers\Api;

use App\Events\UserRegistered;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'city' => $request->city,
            'password' => Hash::make($request->password),
        ]);

        $user->addRole('user');

        event(new UserRegistered($user));

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'user' => $user,
        ])->withCookie(
            cookie(
                'access_token',
                $token,
                60 * 24 * 7, 
                '/',       
                null,       
                false,       
                true,      
                false,       
                'strict'   
            )
        );
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'error' => 'Неверный email или пароль',
            ], 401);
        }

        return response()->json([
            'user' => auth('api')->user(),
        ])->withCookie(
            cookie(
                'access_token',
                $token,
                60 * 24 * 7, 
                '/',
                null,
                false,       
                true,       
                false,
                'strict'
            )
        );
    }

    public function me()
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $permissions = $user->allPermissions();
            $userData = $user->toArray();
            $userData['permissions'] = $permissions;

            return response()->json($userData);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function update(UpdateUserRequest $request)
    {
        $user = auth('api')->user();

        if ($request->name) {
            $user->name = $request->name;
        }

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json($user);
    }

    public function logout()
    {
        try {
            auth('api')->logout();
        } catch (\Exception $e) {
        }

        return response()->json([
            'message' => 'Вы успешно вышли',
        ])->withCookie(cookie('access_token', '', -1, '/'));
    }

    public function refresh()
    {
        try {
            $newToken = auth('api')->refresh();

            return response()->json(['message' => 'Token refreshed'])
                ->withCookie(
                    cookie(
                        'access_token',
                        $newToken,
                        60 * 24 * 7,
                        '/',
                        null,
                        false,
                        true,
                        false,
                        'strict'
                    )
                );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not refresh token'], 401);
        }
    }
}