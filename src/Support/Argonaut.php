<?php

namespace FullStackAppCo\Argonaut\Support;

use ErrorException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class Argonaut
{
    protected array $data;

    protected static array $stores;

    /**
     * Convenience factory method.
     */
    public static function build(string|array $config): static
    {
        return app(static::class, [
            'path' => $config['path'] ?? $config,
            'disk' => data_get($config, 'disk'),
        ]);
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
        protected Filesystem $disk
    )
    {
        //
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

        if (! $this->disk->exists($this->path)) {
            return [];
        }

        return $this->data = json_decode($this->disk->get($this->path), true, JSON_THROW_ON_ERROR);
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
        if (! empty($data)) {
            $this->disk->put($this->path, json_encode($data, JSON_PRETTY_PRINT));
            return $this;
        }

        if ($this->disk->exists($this->path)) {
            $this->disk->delete($this->path);
        }

        return $this;
    }

    public function save(): static
    {
        return $this->write($this->all());
    }
}