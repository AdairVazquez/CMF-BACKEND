<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\Auth\AccountLockedNotification;
use App\Notifications\Auth\LoginSuccessNotification;
use App\Notifications\Auth\PasswordResetNotification;
use App\Notifications\Auth\TwoFactorEnabledNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQRCode;

class AuthService
{
    private const CODE_EXPIRY_MINUTES = 10;

    /**
     * Iniciar sesión con validación de bloqueo y 2FA
     */
    public function login(string $email, string $password, string $ip, string $device): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            Log::channel('security')->warning('Login attempt with non-existent email', [
                'email' => $email,
                'ip'    => $ip,
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
                'email'   => $user->email,
                'ip'      => $ip,
            ]);

            return [
                'success' => false,
                'message' => "Cuenta bloqueada. Intenta de nuevo en {$minutes} minutos.",
                'locked'  => true,
            ];
        }

        if (!$user->is_active) {
            Log::channel('security')->warning('Login attempt on inactive account', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'ip'      => $ip,
            ]);

            return [
                'success' => false,
                'message' => 'Cuenta inactiva. Contacta al administrador.',
            ];
        }

        if (!Hash::check($password, $user->password)) {
            $user->incrementFailedLoginAttempts();

            Log::channel('security')->warning('Failed login attempt', [
                'user_id'  => $user->id,
                'email'    => $user->email,
                'attempts' => $user->failed_login_attempts,
                'ip'       => $ip,
            ]);

            if ($user->failed_login_attempts >= 5) {
                $user->notify(new AccountLockedNotification($user->failed_login_attempts));

                Log::channel('security')->alert('Account locked due to failed attempts', [
                    'user_id'  => $user->id,
                    'email'    => $user->email,
                    'attempts' => $user->failed_login_attempts,
                ]);

                return [
                    'success' => false,
                    'message' => 'Cuenta bloqueada por 15 minutos debido a intentos fallidos.',
                    'locked'  => true,
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

            Cache::put("2fa_token:{$twoFactorToken}", [
                'user_id' => $user->id,
                'ip'      => $ip,
                'device'  => $device,
            ], now()->addMinutes(self::CODE_EXPIRY_MINUTES));

            Log::channel('security')->info('Login successful - 2FA required (TOTP)', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'ip'      => $ip,
            ]);

            return [
                'success'      => true,
                'requires_2fa' => true,
                'token'        => $twoFactorToken,
                'message'      => 'Ingresa el código de 6 dígitos de tu app autenticadora (Google Authenticator o Authy)',
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
            'email'   => $user->email,
            'ip'      => $ip,
            'device'  => $device,
        ]);

        return [
            'success' => true,
            'token'   => $token,
            'user'    => $user->load('roles'),
            'message' => 'Inicio de sesión exitoso',
        ];
    }

    /**
     * Verificar código TOTP de app autenticadora durante el login
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

        $user = User::find($data['user_id']);

        if (!$user || !$user->hasTwoFactorEnabled()) {
            Cache::forget("2fa_token:{$token}");

            return [
                'success' => false,
                'message' => 'Usuario no válido',
            ];
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->two_factor_secret, $code);

        if (!$valid) {
            Log::channel('security')->warning('Invalid TOTP code on login', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            return [
                'success' => false,
                'message' => 'Código incorrecto o expirado',
            ];
        }

        Cache::forget("2fa_token:{$token}");

        $authToken = $user->createToken($data['device'] ?? 'default')->plainTextToken;
        $user->updateLoginInfo($data['ip'], $data['device']);

        Log::channel('security')->info('2FA verified successfully (TOTP)', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return [
            'success' => true,
            'token'   => $authToken,
            'user'    => $user->load('roles'),
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
        $cleanCode     = strtoupper(str_replace('-', '', $recoveryCode));

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
                'email'   => $user->email,
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
            'user_id'         => $user->id,
            'email'           => $user->email,
            'remaining_codes' => count($recoveryCodes),
        ]);

        return [
            'success'         => true,
            'token'           => $authToken,
            'user'            => $user->load('roles'),
            'remaining_codes' => count($recoveryCodes),
            'message'         => 'Autenticación completada con código de recuperación',
        ];
    }

    /**
     * Iniciar configuración de 2FA con TOTP:
     * Genera un secreto TOTP y devuelve el QR code para escanear con Google Authenticator / Authy
     */
    public function enable2FA(User $user): array
    {
        $google2fa = new Google2FAQRCode();
        $secret    = $google2fa->generateSecretKey();

        // Guardar secreto temporal (no confirmado todavía)
        $user->update([
            'two_factor_secret'       => $secret,
            'two_factor_enabled'      => false,
            'two_factor_confirmed_at' => null,
        ]);

        $qrCode = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret
        );

        Log::channel('security')->info('2FA setup initiated (TOTP)', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return [
            'success' => true,
            'qr_code' => $qrCode,
            'secret'  => $secret,
            'message' => 'Escanea el código QR con Google Authenticator o Authy, luego confirma con un código de 6 dígitos',
        ];
    }

    /**
     * Confirmar activación de 2FA verificando el código TOTP de la app
     */
    public function confirm2FA(User $user, string $code): array
    {
        if (!$user->two_factor_secret) {
            return [
                'success' => false,
                'message' => 'Primero debes iniciar la configuración de 2FA',
            ];
        }

        $google2fa = new Google2FA();
        $valid     = $google2fa->verifyKey($user->two_factor_secret, $code);

        if (!$valid) {
            return [
                'success' => false,
                'message' => 'Código incorrecto. Verifica que tu app esté sincronizada con la hora correcta.',
            ];
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_enabled'       => true,
            'two_factor_confirmed_at'  => now(),
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
        ]);

        $user->notify(new TwoFactorEnabledNotification());

        Log::channel('security')->info('2FA enabled successfully (TOTP)', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return [
            'success'        => true,
            'recovery_codes' => $recoveryCodes,
            'message'        => 'Autenticación de dos factores activada correctamente. Guarda estos códigos de recuperación en un lugar seguro.',
        ];
    }

    /**
     * Desactivar 2FA (requiere contraseña verificada por FormRequest)
     */
    public function disable2FA(User $user): array
    {
        $user->update([
            'two_factor_secret'        => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled'       => false,
            'two_factor_confirmed_at'  => null,
        ]);

        Log::channel('security')->info('2FA disabled', [
            'user_id' => $user->id,
            'email'   => $user->email,
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
            'email'   => $user->email,
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
            'user_id'        => $user->id,
            'email'          => $user->email,
            'tokens_revoked' => $tokensCount,
        ]);

        return [
            'success' => true,
            'message' => "Se cerraron {$tokensCount} sesiones activas",
        ];
    }

    /**
     * Solicitar recuperación de contraseña (envía código por email)
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
                'password_reset_token'      => Hash::make($code),
                'password_reset_expires_at' => now()->addMinutes(15),
            ]);

            $user->notify(new PasswordResetNotification($code));

            Log::channel('security')->info('Password reset code sent', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Si el email existe, recibirás un código de recuperación.',
        ];
    }

    /**
     * Verificar código de reset de contraseña y retornar token temporal
     */
    public function verifyResetCode(string $email, string $code): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !$user->password_reset_token || !$user->password_reset_expires_at) {
            return [
                'success' => false,
                'message' => 'Código incorrecto o expirado',
            ];
        }

        if ($user->password_reset_expires_at->isPast()) {
            $user->update([
                'password_reset_token'      => null,
                'password_reset_expires_at' => null,
            ]);

            return [
                'success' => false,
                'message' => 'Código incorrecto o expirado',
            ];
        }

        if (!Hash::check($code, $user->password_reset_token)) {
            Log::channel('security')->warning('Invalid password reset code on verify', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            return [
                'success' => false,
                'message' => 'Código incorrecto o expirado',
            ];
        }

        $resetToken = Str::uuid()->toString();
        $expiresAt  = now()->addMinutes(10);

        Cache::put("reset_token:{$resetToken}", [
            'user_id' => $user->id,
            'email'   => $email,
        ], $expiresAt);

        Log::channel('security')->info('Password reset code verified', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return [
            'success'     => true,
            'reset_token' => $resetToken,
            'expires_at'  => $expiresAt->toIso8601String(),
            'message'     => 'Código verificado correctamente',
        ];
    }

    /**
     * Restablecer contraseña usando reset_token (obtenido desde verify-reset-code)
     */
    public function resetPassword(string $resetToken, string $newPassword): array
    {
        $data = Cache::get("reset_token:{$resetToken}");

        if (!$data) {
            return [
                'success' => false,
                'message' => 'Token expirado o inválido. Solicita un nuevo código.',
            ];
        }

        $user = User::find($data['user_id']);

        if (!$user) {
            Cache::forget("reset_token:{$resetToken}");

            return [
                'success' => false,
                'message' => 'Usuario no encontrado',
            ];
        }

        $user->update([
            'password'                  => Hash::make($newPassword),
            'password_reset_token'      => null,
            'password_reset_expires_at' => null,
        ]);

        $user->tokens()->delete();

        Cache::forget("reset_token:{$resetToken}");

        Log::channel('security')->info('Password reset successful', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return [
            'success' => true,
            'message' => 'Contraseña actualizada correctamente',
        ];
    }

    /**
     * Generar códigos de recuperación 2FA
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
        }

        return $codes;
    }
}
