<?php

namespace FullStackAppCo\Argonaut;

use FullStackAppCo\Argonaut\Contracts\JsonStoreDriverContract;
use FullStackAppCo\Argonaut\Drivers\ArrayDriver;
use FullStackAppCo\Argonaut\Drivers\FilesystemDriver;
use FullStackAppCo\Argonaut\Drivers\JsonStoreDriver;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider as BaseProvider;

class ServiceProvider extends BaseProvider
{
    public function register()
    {
        $this->app->bind(JsonStoreDriver::class, function ($app, $args) {
            return ($app->runningUnitTests() === true)
                ? new ArrayDriver([])
                : $app->make(FilesystemDriver::class)->with($args);
        });

        $this->app->bind(FilesystemDriver::class, function ($app, $args) {
            return new FilesystemDriver(
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