<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

class SwaggerDocsServiceProvider extends ServiceProvider
{
    public function boot(Router $router): void
    {
        // No-op: placeholder if you later want to register routes here.
    }
}

