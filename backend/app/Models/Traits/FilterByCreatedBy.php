<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait FilterByCreatedBy
{
    public function scopeWhereCreatedByForRegularUser(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user && $user->hasRole('regular_user')) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }
}
