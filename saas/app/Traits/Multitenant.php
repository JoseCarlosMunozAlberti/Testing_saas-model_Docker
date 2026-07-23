<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Multitenant
{
    protected static function bootMultitenant(): void
    {
        static::addGlobalScope(
            'tenant',
            function (Builder $builder): void {
                if (auth()->check()) {
                    $builder->where(
                        $builder->getModel()->qualifyColumn('tenant_id'),
                        auth()->user()->tenant_id
                    );
                }
            }
        );

        static::creating(function ($model): void {
            if (
                auth()->check() &&
                empty($model->tenant_id)
            ) {
                $model->tenant_id =
                    auth()->user()->tenant_id;
            }
        });
    }
}
