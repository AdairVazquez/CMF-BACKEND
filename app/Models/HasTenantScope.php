<?php

namespace App\Models;

use App\Models\TenantScope;

trait HasTenantScope
{
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}