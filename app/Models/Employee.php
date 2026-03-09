<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'shift_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'employee_type',
        'status',
        'hire_date',
        'termination_date',
        'position',
        'hierarchy_level',
        'metadata',
    ];

    protected $casts = [
        'employee_type' => EmployeeType::class,
        'status' => EmployeeStatus::class,
        'hire_date' => 'date',
        'termination_date' => 'date',
        'hierarchy_level' => 'integer',
        'metadata' => 'array',
    ];

    protected $hidden = ['deleted_at'];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function nfcCard(): HasOne
    {
        return $this->hasOne(NfcCard::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeBase($query)
    {
        return $query->where('employee_type', EmployeeType::BASE);
    }

    public function scopeConfianza($query)
    {
        return $query->where('employee_type', EmployeeType::CONFIANZA);
    }

    public function scopeActive($query)
    {
        return $query->where('status', EmployeeStatus::ACTIVO);
    }

    public function isBase(): bool
    {
        return $this->employee_type === EmployeeType::BASE;
    }

    public function isConfianza(): bool
    {
        return $this->employee_type === EmployeeType::CONFIANZA;
    }

    public function canRequestLeave(): bool
    {
        return $this->isBase() && 
               $this->status === EmployeeStatus::ACTIVO &&
               $this->company->hasModuleActive(Company::MODULE_AUSENCIAS);
    }
}
