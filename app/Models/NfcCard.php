<?php

namespace App\Models;

use App\Enums\CardStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NfcCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'employee_id',
        'card_uid',
        'status',
        'issued_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'status' => CardStatus::class,
        'issued_at' => 'date',
        'expires_at' => 'date',
    ];

    protected $hidden = ['deleted_at'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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
        return $query->where('status', CardStatus::ACTIVA);
    }

    public function isActive(): bool
    {
        return $this->status === CardStatus::ACTIVA;
    }

    public function isAvailable(): bool
    {
        return $this->status === CardStatus::ACTIVA && is_null($this->employee_id);
    }
}
