<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'device_code',
        'name',
        'location',
        'status',
        'ip_address',
        'mac_address',
        'last_ping_at',
        'config',
    ];

    protected $casts = [
        'status' => DeviceStatus::class,
        'last_ping_at' => 'datetime',
        'config' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', DeviceStatus::ACTIVO);
    }

    public function isOnline(): bool
    {
        if (!$this->last_ping_at) {
            return false;
        }

        return $this->last_ping_at->diffInMinutes(now()) < 5;
    }
}
