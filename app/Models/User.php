<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'phone',
        'is_super_admin',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'last_login_device',
        'email_verified_at',
        'two_factor_enabled',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'password_reset_token',
        'password_reset_expires_at',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'password_reset_token',
        'deleted_at',
    ];

    protected function casts(): array
    {
        $casts = [
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'email_verified_at' => 'datetime',
            'password_reset_expires_at' => 'datetime',
            'failed_login_attempts' => 'integer',
        ];

        // Solo encriptar si APP_KEY está configurada
        if (config('app.key')) {
            $casts['two_factor_secret'] = 'encrypted';
            $casts['two_factor_recovery_codes'] = 'encrypted';
        }

        return $casts;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function attendanceLogsRegistered(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'registered_by');
    }

    public function leaveRequestsApprovedAsManager(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'approved_by_manager');
    }

    public function leaveRequestsApprovedAsHr(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'approved_by_hr');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeSuperAdmins($query)
    {
        return $query->where('is_super_admin', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin;
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }

    public function assignRole(Role $role): void
    {
        $this->roles()->syncWithoutDetaching($role);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && $this->two_factor_confirmed_at !== null;
    }

    public function isAccountLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function incrementFailedLoginAttempts(): void
    {
        $this->increment('failed_login_attempts');
        
        if ($this->failed_login_attempts >= 5) {
            $this->locked_until = now()->addMinutes(15);
            $this->save();
        }
    }

    public function resetFailedLoginAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    public function updateLoginInfo(string $ip, string $device): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'last_login_device' => $device,
        ]);
    }
}
