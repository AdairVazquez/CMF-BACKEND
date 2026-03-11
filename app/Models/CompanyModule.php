<?php

namespace App\Models;

use App\Models\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyModule extends Model
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'company_id',
        'module_name',
        'is_active',
        'activated_at',
        'expires_at',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'activated_at' => 'date',
        'expires_at' => 'date',
        'config' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }
}
