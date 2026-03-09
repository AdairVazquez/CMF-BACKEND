<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'tax_id',
        'email',
        'phone',
        'address',
        'logo',
        'plan',
        'status',
        'timezone',
        'modules',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    protected $casts = [
        'status' => CompanyStatus::class,
        'modules' => 'array',
        'trial_ends_at' => 'date',
        'subscription_ends_at' => 'date',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function nfcCards(): HasMany
    {
        return $this->hasMany(NfcCard::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function companyModules(): HasMany
    {
        return $this->hasMany(CompanyModule::class);
    }

    public function attendanceRules(): HasMany
    {
        return $this->hasMany(AttendanceRule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', CompanyStatus::ACTIVO);
    }

    public function hasModuleActive(string $moduleName): bool
    {
        return $this->companyModules()
            ->where('module_name', $moduleName)
            ->where('is_active', true)
            ->exists();
    }
}
