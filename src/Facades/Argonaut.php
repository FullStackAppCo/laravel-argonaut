<?php

namespace FullStackAppCo\Argonaut\Facades;

use FullStackAppCo\Argonaut\Drivers\JsonStoreDriver;
use FullStackAppCo\Argonaut\JsonStoreManager;
use Illuminate\Support\Facades\Facade;

class Argonaut extends Facade
{
    public static function getFacadeAccessor()
    {
        return JsonStoreManager::class;
    }

    public static function fake(string $name): JsonStoreDriver
    {
        static::set($name, (new JsonStoreManager)->store($name));
        return static::store($name);
    }
}
