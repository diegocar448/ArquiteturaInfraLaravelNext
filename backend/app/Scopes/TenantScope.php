<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth('api')->user();

        // Super-admin (sem tenant) ve tudo — nao aplica filtro
        if ($user && $user->tenant_id) {
            $builder->where($model->getTable().'.tenant_id', $user->tenant_id);
        }
    }
}
