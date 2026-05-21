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
            // If we are running in the console or a test runner Arrange step, 
            // don't apply the restrictive scope unless a request context explicitly exists.
            if (app()->runningInConsole() && !request()->attributes->has('organisation')) {
                return;
            }
            $organisation = request()->attributes->get('organisation');

            if ($organisation instanceof Organisation) {
                $builder->where('organisation_id', $organisation->id);
            }
            else {
                // Fail closed: No organization found, return no records.
                $builder->whereNull('id'); 
            }
        });

        // Automatically assign the organisation_id when creating a new record
        static::creating(function (Model $model) {
            $organisation = request()->attributes->get('organisation');

            if ($organisation instanceof Organisation) {
                if (!$model->organisation_id) {
                    $model->organisation_id = $organisation->id;
                }
            } else {
                // If there's no organization context and no ID manually assigned, block the creation.
                if (!$model->organisation_id) {
                    throw new \Exception("Cannot create record: Multi-tenant organization context is missing.");
                }
            }
        });
    }
}
