<?php

namespace FullStackAppCo\Argonaut;

use FullStackAppCo\Argonaut\JsonStore;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider as BaseProvider;

class ServiceProvider extends BaseProvider
{
    public function register()
    {
        $this->app->bind(JsonStore::class, function ($app, $args) {
            return new JsonStore(
                $args['path'],
                with($args['disk'] ?? null, fn ($disk) =>
                    $disk instanceof Filesystem
                        ? $disk
                        : $app->make('filesystem')->disk($disk)
                )
            );
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/argonaut.php' => config_path('argonaut.php')
        ], 'config');
    }
}