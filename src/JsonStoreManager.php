<?php

namespace FullStackAppCo\Argonaut;

use ErrorException;
use FullStackAppCo\Argonaut\Drivers\JsonStoreDriver;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class JsonStoreManager
{
    protected array $stores = [];

    /**
     * Get an instance based on configuration file.
     */
    public function store(string $name): JsonStoreDriver
    {
        $config = Config::get('argonaut.stores')[$name] ?? null;

        if ($config === null) {
            throw new ErrorException("Store '{$name}' is not configured");
        }

        return $this->stores[$name]
            ?? $this->stores[$name] = $this->build($config);
    }

    public function build(array $config): JsonStoreDriver
    {
        return App::make(JsonStoreDriver::class, $config);
    }


    /**
     * @deprecated v2.1.0
     */
    public function set(string $name, JsonStoreDriver $store) : JsonStoreManager
    {
        $this->stores[$name] = $store;
        return $this;
    }
}