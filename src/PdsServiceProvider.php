<?php

namespace KawayanMedia\Pds;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the CS Form 212 migrations with the host application.
 */
class PdsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
