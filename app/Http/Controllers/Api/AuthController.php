<?php

namespace App\Http\Controllers\Api;

use App\Events\UserRegistered;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Mail;
use Password;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Validator;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
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

public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ], [
            'email.exists' => 'Пользователь с таким email не найден.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Создаем токен
        $token = Password::getRepository()->create($user);
        
        // Формируем URL для сброса (фронтенд роут)
        // Для Vue приложения обычно отдельный маршрут, например: /reset-password?token=...&email=...
        $resetUrl = env('APP_URL') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        
        // Отправляем письмо
        Mail::to($user->email)->send(new ResetPasswordMail($user->name, $resetUrl));
        
        return response()->json([
            'success' => true,
            'message' => 'Ссылка для восстановления пароля отправлена на вашу почту!'
        ]);
    }

public function sendResetLinkStandard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $status = Password::sendResetLink(
            $request->only('email'),
            function ($user, $token) {
                $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
                Mail::to($user->email)->send(new ResetPasswordMail($user->name, $resetUrl));
            }
        );
        
        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Ссылка для восстановления пароля отправлена на вашу почту!'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'errors' => [
                'email' => [__($status)]
            ]
        ], 422);
    }
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password)
                ])->save();
            }
        );
        
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Пароль успешно изменён!'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'errors' => [
                'email' => [__($status)]
            ]
        ], 422);
    }
}