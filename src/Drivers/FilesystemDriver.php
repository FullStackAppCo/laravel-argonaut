<?php

namespace FullStackAppCo\Argonaut\Drivers;

use FullStackAppCo\Argonaut\JsonStoreManager;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;

class FilesystemDriver extends JsonStoreDriver
{
    /**
     * @var JsonStoreManager
     */
    protected $store;


    public function __construct(
        protected string $path,
        protected Filesystem $disk,
        array $defaults = [],
    )
    {
        parent::__construct($defaults);
    }

    protected function write(array $data): JsonStoreDriver
    {
        Cache::forget($this->cacheKey());

        if (empty($data) === true) {
            if ($this->disk->exists($this->path)) {
                $this->disk->delete($this->path);
            }
        }

        $encoded = $this->encode($data);

        (empty($data) === true)
            ? $this->disk->delete($this->path)
            : $this->disk->put($this->path, $encoded);

        return $this;
    }

    public function encode(array $data)
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function read(): array
    {
        if (Cache::has($this->cacheKey())) {
            return Cache::get($this->cacheKey());
        }

        $value = null;

        if ($this->disk->exists($this->path) === true) {
            $value = json_decode($this->disk->get($this->path), $associative = true, JSON_THROW_ON_ERROR);
        }

        $value = $value ?? [];
        Cache::put($this->cacheKey(), $value);

        return $this->data = $value;
    }

    public function path($absolute = false): string
    {
        return $absolute ? $this->disk->path($this->path) : $this->path;
    }

    public function cacheKey(): string
    {
        return 'argonaut:' . md5($this->path(true));
    }
}
