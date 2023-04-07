<?php

namespace FullStackAppCo\Argonaut\Drivers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

abstract class JsonStoreDriver
{

    protected array $data;

    protected function __construct(
        protected array $defaults = []
    )
    {
        //
    }

    /**
     * Retrieve data.
     */
    abstract public function read(): array;

    /**
     * Persist data.
     */
    abstract protected function write(array $data): JsonStoreDriver;

    public function all(): array
    {
        if (isset($this->data)) {
            return $this->data;
        }

        return $this->data = $this->read();
    }

    public function put(string $key, $value): JsonStoreDriver
    {
        $all = $this->all();
        $this->data = data_set($all, $key, $value);
        return $this;
    }

    public function get(string $key)
    {
        $data = $this->all();
        return data_get($data, $key, data_get($this->defaults, $key));
    }

    public function forget(string $key): JsonStoreDriver
    {
        $all = $this->all();
        Arr::forget($all, $key);
        $this->data = $all;
        return $this;
    }

    public function save(): JsonStoreDriver
    {
        $this->write($this->all());
        return $this;
    }
}