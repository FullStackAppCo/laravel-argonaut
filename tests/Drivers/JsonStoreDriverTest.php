<?php

namespace Tests\Drivers;

use FullStackAppCo\Argonaut\Drivers\JsonStoreDriver;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JsonStoreDriverTest extends TestCase
{

    public function storeInstance(array $data = [], array $defaults = [])
    {
        return new class($data, $defaults) extends JsonStoreDriver
        {
            public function __construct(
                protected array $data,
                array $defaults
            )
            {
                parent::__construct($defaults);
            }

            public function read(): array
            {
                return $this->data;
            }

            protected function write(array $data): JsonStoreDriver
            {
                return $this;
            }
        };
    }

    public function test_get()
    {
        $store = $this->storeInstance();
        $store->put('color', '#F00BA9');

        $this->assertSame('#F00BA9', $store->get('color'));
    }

    public function test_get_uses_dot_syntax()
    {
        $store = $this->storeInstance();
        $store->put('color.primary', '#F00BA9');

        $this->assertSame('#F00BA9', $store->get('color.primary'));
        $this->assertSame('#F00BA9', $store->all()['color']['primary']);
    }

    public function test_get_uses_defaults()
    {
        $store = $this->storeInstance(defaults: ['color' => '#BADA55']);
        $this->assertSame('#BADA55', $store->get('color'));

        Config::set('argonaut.stores.test.defaults.color', ['primary' => '#000']);
        $store = $this->storeInstance(defaults: ['color' => ['primary' => '#000']]);
        $this->assertSame('#000', $store->get('color.primary'));
    }

    public function test_forget()
    {
        Storage::fake();
        $store = $this->storeInstance()->put('foo', 'bar');
        $this->assertSame('bar', $store->get('foo'));

        $store->forget('foo');
        $this->assertNull($store->get('foo'));
    }

}