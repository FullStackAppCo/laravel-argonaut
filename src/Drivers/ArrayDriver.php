<?php

namespace FullStackAppCo\Argonaut\Drivers;

use Illuminate\Support\Collection;

class ArrayDriver extends JsonStoreDriver
{

    /**
     * @var Collection
     */
    public $collection;

    public function __construct(
        array $state = [],
        array $defaults = []
    )
    {
        $this->collection = Collection::make($state);
        parent::__construct($defaults);
    }

    protected function write(array $array): JsonStoreDriver
    {
        $this->collection = collect($array);
        return $this;
    }

    public function read(): array
    {
        return $this->collection->toArray();
    }
}
