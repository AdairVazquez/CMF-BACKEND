<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->boolean('two_factor_enabled')->default(false)->after('two_factor_confirmed_at');
            
            $table->timestamp('last_login_at')->nullable()->after('two_factor_enabled');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->string('last_login_device')->nullable()->after('last_login_ip');
            
            $table->unsignedInteger('failed_login_attempts')->default(0)->after('last_login_device');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            
            $table->timestamp('email_verified_at')->nullable()->after('locked_until');
            $table->string('password_reset_token')->nullable()->after('email_verified_at');
            $table->timestamp('password_reset_expires_at')->nullable()->after('password_reset_token');
            
            $table->index('locked_until');
            $table->index('password_reset_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['locked_until']);
            $table->dropIndex(['password_reset_token']);
            
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'two_factor_enabled',
                'last_login_at',
                'last_login_ip',
                'last_login_device',
                'failed_login_attempts',
                'locked_until',
                'email_verified_at',
                'password_reset_token',
                'password_reset_expires_at',
            ]);
        });
    }
};
