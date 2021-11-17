<?php

namespace FullStackAppCo\Argonaut;

use ErrorException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class JsonStore
{
    protected array $data;

    protected static array $stores;

    protected bool $testing = false;

    /**
     * Convenience factory method.
     */
    public static function build(string|array $config): static
    {
        $store = app(static::class, [
            'path' => $config['path'] ?? $config,
            'disk' => data_get($config, 'disk'),
        ]);

        if (App::environment('testing') === true) {
            $store->testing();
        }

        return $store;
    }

    /**
     * Get an instance based on configuration file.
     */
    public static function store(string $name): static {
        $config = Config::get('argonaut.stores')[$name]
            ?? throw new ErrorException("Store '{$name}' is not configured");

        return static::$stores[$name] ?? static::$stores[$name] = static::build($config);
    }

    public function __construct(
        protected string|array $path,
        protected Filesystem $disk,
    )
    {
        //
    }

    public function testing(bool $testing = true): static
    {
        $this->testing = $testing;
        return $this;
    }

    public function path($absolute = false): string
    {
        return $absolute ? $this->disk->path($this->path) : $this->path;
    }

    public function all(): array
    {
        if (isset($this->data)) {
            return $this->data;
        }

        if (Cache::has($this->cacheKey())) {
            $decoded = json_decode(Cache::get($this->cacheKey()), $associative = true);
            if ($decoded !== null) {
                return $this->data = $decoded;
            }
        }

        if ($this->disk->exists($this->path) === false) {
            return [];
        }

        return $this->data = json_decode($this->disk->get($this->path), $associative = true, JSON_THROW_ON_ERROR);
    }

    public function put(string $key, mixed $value): static
    {
        $all = $this->all();
        $this->data = data_set($all, $key, $value);
        return $this;
    }

    public function get(string $key): mixed
    {
        $data = $this->all();
        return data_get($data, $key);
    }

    public function forget(string $key): static
    {
        $all = $this->all();
        Arr::forget($all, $key);
        $this->data = $all;
        return $this;
    }

    protected function write(array $data): static
    {
        if ($this->testing === true) {
            return $this;
        }

        if (empty($data) === true) {
            if ($this->disk->exists($this->path)) {
                $this->disk->delete($this->path);
            }

            Cache::forget($this->cacheKey());

            return $this;
        }

        tap(json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), function ($encoded) {
            $this->disk->put($this->path, $encoded);
            Cache::put($this->cacheKey(), $encoded);
        });

        return $this;
    }

    protected function cacheKey(): string
    {
        return 'argonaut:' . md5($this->path(true));
    }

    public function save(): static
    {
        return $this->write($this->all());
    }
}