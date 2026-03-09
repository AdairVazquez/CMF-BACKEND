<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'status',
        'leave_type',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'rejection_reason',
        'approved_by_manager',
        'approved_by_manager_at',
        'approved_by_hr',
        'approved_by_hr_at',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'status' => LeaveStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'days_requested' => 'integer',
        'approved_by_manager_at' => 'datetime',
        'approved_by_hr_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedByManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_manager');
    }

    public function approvedByHr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_hr');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePending($query)
    {
        return $query->where('status', LeaveStatus::PENDIENTE);
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', [LeaveStatus::APROBADO_JEFE, LeaveStatus::APROBADO_RH]);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', LeaveStatus::RECHAZADO);
    }

    public function isPending(): bool
    {
        return $this->status === LeaveStatus::PENDIENTE;
    }

    public function isFullyApproved(): bool
    {
        return $this->status === LeaveStatus::APROBADO_RH;
    }
}
