<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetActiveOrganisation;
use App\Http\Middleware\SetActiveOrganisationFromSession;
use Illuminate\Routing\Middleware\SubstituteBindings;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'org_context' => SetActiveOrganisation::class,
            'active_org' => SetActiveOrganisationFromSession::class,
        ]);

        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: SetActiveOrganisationFromSession::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
