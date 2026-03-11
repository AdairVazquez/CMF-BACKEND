<?php

namespace App\Models;

use App\Models\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRule extends Model
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'company_id',
        'late_tolerance_minutes',
        'early_departure_tolerance_minutes',
        'allow_overtime',
        'overtime_multiplier',
        'max_overtime_hours_per_day',
        'require_checkout',
        'auto_checkout_enabled',
        'auto_checkout_time',
        'apply_penalty_for_late',
        'penalty_amount_per_minute',
    ];

    protected $casts = [
        'late_tolerance_minutes' => 'integer',
        'early_departure_tolerance_minutes' => 'integer',
        'allow_overtime' => 'boolean',
        'overtime_multiplier' => 'decimal:2',
        'max_overtime_hours_per_day' => 'integer',
        'require_checkout' => 'boolean',
        'auto_checkout_enabled' => 'boolean',
        'auto_checkout_time' => 'datetime:H:i',
        'apply_penalty_for_late' => 'boolean',
        'penalty_amount_per_minute' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
