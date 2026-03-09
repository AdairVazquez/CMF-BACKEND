<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\Auth\AccountLockedNotification;
use App\Notifications\Auth\LoginSuccessNotification;
use App\Notifications\Auth\PasswordResetNotification;
use App\Notifications\Auth\TwoFactorCodeNotification;
use App\Notifications\Auth\TwoFactorEnabledNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthService
{
    private const CODE_EXPIRY_MINUTES = 10;
    private const CONFIRM_CODE_EXPIRY_MINUTES = 5;

    /**
     * Iniciar sesión con validación de bloqueo y 2FA
     */
    public function login(string $email, string $password, string $ip, string $device): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            Log::channel('security')->warning('Login attempt with non-existent email', [
                'email' => $email,
                'ip' => $ip,
            ]);

            return [
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ];
        }

        if ($user->isAccountLocked()) {
            $minutes = now()->diffInMinutes($user->locked_until);
            
            Log::channel('security')->warning('Login attempt on locked account', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $ip,
            ]);

            return [
                'success' => false,
                'message' => "Cuenta bloqueada. Intenta de nuevo en {$minutes} minutos.",
                'locked' => true,
            ];
        }

        if (!$user->is_active) {
            Log::channel('security')->warning('Login attempt on inactive account', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $ip,
            ]);

            return [
                'success' => false,
                'message' => 'Cuenta inactiva. Contacta al administrador.',
            ];
        }

        if (!Hash::check($password, $user->password)) {
            $user->incrementFailedLoginAttempts();

            Log::channel('security')->warning('Failed login attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'attempts' => $user->failed_login_attempts,
                'ip' => $ip,
            ]);

            if ($user->failed_login_attempts >= 5) {
                $user->notify(new AccountLockedNotification($user->failed_login_attempts));
                
                Log::channel('security')->alert('Account locked due to failed attempts', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'attempts' => $user->failed_login_attempts,
                ]);

                return [
                    'success' => false,
                    'message' => 'Cuenta bloqueada por 15 minutos debido a intentos fallidos.',
                    'locked' => true,
                ];
            }

            return [
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ];
        }

        $user->resetFailedLoginAttempts();

        if ($user->hasTwoFactorEnabled()) {
            $twoFactorToken = Str::uuid()->toString();
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            Cache::put("2fa_token:{$twoFactorToken}", [
                'user_id' => $user->id,
                'ip' => $ip,
                'device' => $device,
            ], now()->addMinutes(self::CODE_EXPIRY_MINUTES));

            Cache::put("2fa_code:{$twoFactorToken}", $code, now()->addMinutes(self::CODE_EXPIRY_MINUTES));

            try {
                $user->notify(new TwoFactorCodeNotification($code, self::CODE_EXPIRY_MINUTES, 'login'));
            } catch (\Throwable $e) {
                Log::channel('mail')->error('Error enviando código 2FA (login)', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return [
                    'success' => false,
                    'message' => 'No se pudo enviar el correo con el código. Revisa MAIL_* en .env y storage/logs/mail.log',
                ];
            }

            Log::channel('security')->info('Login successful - 2FA code sent by email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $ip,
            ]);

            return [
                'success' => true,
                'requires_2fa' => true,
                'token' => $twoFactorToken,
                'message' => 'Revisa tu correo e ingresa el código de 6 dígitos',
            ];
        }

        $token = $user->createToken($device ?? 'default')->plainTextToken;
        $user->updateLoginInfo($ip, $device);

        if ($user->last_login_ip !== $ip || $user->last_login_device !== $device) {
            $user->notify(new LoginSuccessNotification(
                $ip,
                $device,
                now()->format('d/m/Y H:i:s')
            ));
        }

        Log::channel('security')->info('Login successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $ip,
            'device' => $device,
        ]);

        return [
            'success' => true,
            'token' => $token,
            'user' => $user->load('roles'),
            'message' => 'Inicio de sesión exitoso',
        ];
    }

    /**
     * Verificar código 2FA (enviado por correo)
     */
    public function verify2FA(string $token, string $code): array
    {
        $data = Cache::get("2fa_token:{$token}");

        if (!$data) {
            return [
                'success' => false,
                'message' => 'Token expirado o inválido',
            ];
        }

        $cachedCode = Cache::get("2fa_code:{$token}");

        if ($cachedCode === null || $cachedCode !== $code) {
            $user = User::find($data['user_id'] ?? null);
            Log::channel('security')->warning('Invalid 2FA code', [
                'user_id' => $user?->id,
                'email' => $user?->email,
            ]);

            return [
                'success' => false,
                'message' => 'Código incorrecto o expirado',
            ];
        }

        $user = User::find($data['user_id']);

        if (!$user || !$user->hasTwoFactorEnabled()) {
            Cache::forget("2fa_token:{$token}");
            Cache::forget("2fa_code:{$token}");
            return [
                'success' => false,
                'message' => 'Usuario no válido',
            ];
        }

        Cache::forget("2fa_token:{$token}");
        Cache::forget("2fa_code:{$token}");

        $authToken = $user->createToken($data['device'] ?? 'default')->plainTextToken;
        $user->updateLoginInfo($data['ip'], $data['device']);

        Log::channel('security')->info('2FA verified successfully (email code)', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'success' => true,
            'token' => $authToken,
            'user' => $user->load('roles'),
            'message' => 'Autenticación completada',
        ];
    }

    /**
     * Usar código de recuperación 2FA
     */
    public function useRecoveryCode(string $token, string $recoveryCode): array
    {
        $data = Cache::get("2fa_token:{$token}");

        if (!$data) {
            return [
                'success' => false,
                'message' => 'Token expirado o inválido',
            ];
        }

        $user = User::find($data['user_id']);

        if (!$user || !$user->hasTwoFactorEnabled()) {
            Cache::forget("2fa_token:{$token}");
            return [
                'success' => false,
                'message' => 'Usuario no válido',
            ];
        }

        $recoveryCodes = json_decode($user->two_factor_recovery_codes, true) ?? [];
        $cleanCode = strtoupper(str_replace('-', '', $recoveryCode));

        $foundIndex = null;
        foreach ($recoveryCodes as $index => $code) {
            if (strtoupper(str_replace('-', '', $code)) === $cleanCode) {
                $foundIndex = $index;
                break;
            }
        }

        if ($foundIndex === null) {
            Log::channel('security')->warning('Invalid recovery code', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'success' => false,
                'message' => 'Código de recuperación inválido',
            ];
        }

        unset($recoveryCodes[$foundIndex]);
        $user->two_factor_recovery_codes = json_encode(array_values($recoveryCodes));
        $user->save();

        Cache::forget("2fa_token:{$token}");
        
        $authToken = $user->createToken($data['device'] ?? 'default')->plainTextToken;
        $user->updateLoginInfo($data['ip'], $data['device']);

        Log::channel('security')->info('Recovery code used successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'remaining_codes' => count($recoveryCodes),
        ]);

        return [
            'success' => true,
            'token' => $authToken,
            'user' => $user->load('roles'),
            'remaining_codes' => count($recoveryCodes),
            'message' => 'Autenticación completada con código de recuperación',
        ];
    }

    /**
     * Habilitar 2FA por correo: envía código al email para confirmar
     */
    public function enable2FA(User $user): array
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put("2fa_confirm:{$user->id}", $code, now()->addMinutes(self::CONFIRM_CODE_EXPIRY_MINUTES));

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ]);

        try {
            $subject = "Código 2FA: {$code} - " . config('app.name');
            $body = "¡Hola {$user->name}!\n\n" .
                    "Para activar 2FA, tu código de verificación es:\n\n" .
                    "===== {$code} =====\n\n" .
                    "Válido por " . self::CONFIRM_CODE_EXPIRY_MINUTES . " minutos.\n\n" .
                    "Si recibiste este mensaje, la configuración está funcionando.\n\n" .
                    "Saludos, " . config('app.name');

            \Illuminate\Support\Facades\Mail::raw($body, function ($message) use ($user, $subject) {
                $message->to($user->email)->subject($subject);
            });
            
            Log::channel('mail')->info('2FA Enable - Correo enviado', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } catch (\Throwable $e) {
            Log::channel('mail')->error('Error enviando código 2FA (enable)', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'No se pudo enviar el correo. Error: ' . $e->getMessage(),
            ];
        }

        Log::channel('security')->info('2FA setup initiated (email)', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'success' => true,
            'message' => 'Revisa tu correo e ingresa el código de 6 dígitos para activar 2FA',
        ];
    }

    /**
     * Confirmar activación de 2FA con el código enviado por correo
     */
    public function confirm2FA(User $user, string $code): array
    {
        $cachedCode = Cache::get("2fa_confirm:{$user->id}");

        if ($cachedCode === null) {
            return [
                'success' => false,
                'message' => 'Código expirado. Solicita uno nuevo desde Habilitar 2FA.',
            ];
        }

        if ($cachedCode !== $code) {
            return [
                'success' => false,
                'message' => 'Código incorrecto. Intenta de nuevo.',
            ];
        }

        Cache::forget("2fa_confirm:{$user->id}");

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
        ]);

        $user->notify(new TwoFactorEnabledNotification());

        Log::channel('security')->info('2FA enabled successfully (email)', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'success' => true,
            'recovery_codes' => $recoveryCodes,
            'message' => 'Autenticación de dos factores activada por correo. Guarda estos códigos de recuperación.',
        ];
    }

    /**
     * Desactivar 2FA
     */
    public function disable2FA(User $user): array
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ]);

        Log::channel('security')->info('2FA disabled', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'success' => true,
            'message' => 'Autenticación de dos factores desactivada',
        ];
    }

    /**
     * Cerrar sesión (revocar token actual)
     */
    public function logout(User $user): array
    {
        $user->currentAccessToken()->delete();

        Log::channel('security')->info('User logged out', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ];
    }

    /**
     * Cerrar todas las sesiones
     */
    public function logoutAllDevices(User $user): array
    {
        $tokensCount = $user->tokens()->count();
        $user->tokens()->delete();

        Log::channel('security')->info('All devices logged out', [
            'user_id' => $user->id,
            'email' => $user->email,
            'tokens_revoked' => $tokensCount,
        ]);

        return [
            'success' => true,
            'message' => "Se cerraron {$tokensCount} sesiones activas",
        ];
    }

    /**
     * Solicitar recuperación de contraseña
     */
    public function forgotPassword(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            Log::channel('security')->info('Password reset requested for non-existent email', [
                'email' => $email,
            ]);
        } else {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $user->update([
                'password_reset_token' => Hash::make($code),
                'password_reset_expires_at' => now()->addMinutes(15),
            ]);

            $user->notify(new PasswordResetNotification($code));

            Log::channel('security')->info('Password reset code sent', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Si el email existe, recibirás un código de recuperación.',
        ];
    }

    /**
     * Restablecer contraseña con código
     */
    public function resetPassword(string $email, string $code, string $newPassword): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Datos incorrectos',
            ];
        }

        if (!$user->password_reset_token || !$user->password_reset_expires_at) {
            return [
                'success' => false,
                'message' => 'No hay una solicitud de recuperación activa',
            ];
        }

        if ($user->password_reset_expires_at->isPast()) {
            $user->update([
                'password_reset_token' => null,
                'password_reset_expires_at' => null,
            ]);

            return [
                'success' => false,
                'message' => 'El código ha expirado',
            ];
        }

        if (!Hash::check($code, $user->password_reset_token)) {
            Log::channel('security')->warning('Invalid password reset code', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'success' => false,
                'message' => 'Código incorrecto',
            ];
        }

        $user->update([
            'password' => Hash::make($newPassword),
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
        ]);

        $user->tokens()->delete();

        Log::channel('security')->info('Password reset successful', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'success' => true,
            'message' => 'Contraseña actualizada correctamente',
        ];
    }

    /**
     * Generar códigos de recuperación
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
            $codes[] = $code;
        }
        return $codes;
    }
}
