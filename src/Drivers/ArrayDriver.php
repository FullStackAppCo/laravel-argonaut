<?php

namespace FullStackAppCo\Argonaut\Drivers;

use Illuminate\Support\Collection;

class ArrayDriver extends JsonStoreDriver
{

    public Collection $collection;

    public function __construct(array $state = [])
    {
        $this->collection = Collection::make($state);
    }

    protected function write(array $array): self
    {
        $this->collection = collect($array);
        return $this;
    }

    public function read(): array
    {
        return $this->collection->toArray();
    }
}
