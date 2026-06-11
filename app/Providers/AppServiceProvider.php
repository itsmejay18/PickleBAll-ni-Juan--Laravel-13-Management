<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Catch lazy loading and missing attributes during development.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Force HTTPS URL generation in production / when the request was forwarded as HTTPS.
        if ($this->app->isProduction() || request()->isSecure()) {
            URL::forceScheme('https');
        }
    }
}
