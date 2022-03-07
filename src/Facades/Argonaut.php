<?php

namespace FullStackAppCo\Argonaut\Facades;

use FullStackAppCo\Argonaut\JsonStoreManager;
use Illuminate\Support\Facades\Facade;

class Argonaut extends Facade
{
    public static function getFacadeAccessor()
    {
        return JsonStoreManager::class;
    }
}