<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // No aplicar el scope en comandos de consola (seeders, etc.)
        if (app()->runningInConsole()) {
            return;
        }

        $user = Auth::user();

        // Si el usuario está autenticado, tiene una empresa y no es Super Admin, aplicamos el scope.
        if ($user && $user->company_id && !$user->isSuperAdmin()) {
            $builder->where($model->getTable() . '.company_id', $user->company_id);
        }
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenantScope', fn (Builder $builder) => $builder->withoutGlobalScope($this));
    }
}