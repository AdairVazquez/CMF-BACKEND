<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class Reset2FACommand extends Command
{
    protected $signature = 'user:reset-2fa {email}';

    protected $description = 'Resetear 2FA de un usuario';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario no encontrado: {$email}");
            return Command::FAILURE;
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        $this->info("✓ 2FA reseteado para {$user->email}");
        return Command::SUCCESS;
    }
}
