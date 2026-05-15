<?php

namespace App\Traits;

use App\Models\Organisation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToOrganisation
{
    protected static function bootBelongsToOrganisation(): void
    {
        // Apply the global scope only if we are in an HTTP request 
        // and have an active organisation set by our middleware.
        static::addGlobalScope('organisation_access', function (Builder $builder){
            $organisation = request()->attributes->get('organisation');

            if ($organisation instanceof Organisation) {
                $builder->where('organisation_id', $organisation->id);
            }
        });

        // Automatically assign the organisation_id when creating a new record
        static::creating(function (Model $model) {
            $organisation = request()->attributes->get('organisation');

            if ($organisation instanceof Organisation && !$model->organisation_id){
                $model->organisation_id = $organisation->id;
            }
        });
    }
}
