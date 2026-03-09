<?php

namespace App\Models;

use App\Enums\AttendanceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'device_id',
        'nfc_card_id',
        'type',
        'recorded_at',
        'is_manual',
        'registered_by',
        'latitude',
        'longitude',
        'notes',
    ];

    protected $casts = [
        'type' => AttendanceType::class,
        'recorded_at' => 'datetime',
        'is_manual' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function nfcCard(): BelongsTo
    {
        return $this->belongsTo(NfcCard::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeEntrances($query)
    {
        return $query->where('type', AttendanceType::ENTRADA);
    }

    public function scopeExits($query)
    {
        return $query->where('type', AttendanceType::SALIDA);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }
}
