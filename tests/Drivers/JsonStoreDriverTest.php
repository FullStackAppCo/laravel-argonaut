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
            protected array $source;

            public function __construct(
                array $data,
                array $defaults
            )
            {
                $this->source = $data;
                parent::__construct($defaults);
            }

            public function read(): array
            {
                return $this->source;
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


    public function test_all()
    {
        $store = $this->storeInstance([
            'color' => '#BADA55',
            'font' => 'Menlo'
        ]);

        $this->assertSame([
            'color' => '#BADA55',
            'font' => 'Menlo'
        ], $store->all());
    }

    public function test_all_merges_defaults()
    {
        $store = $this->storeInstance(
            data: ['font' => 'Menlo'],
            defaults: [
                'font' => 'Comic Sans',
                'color' => '#BADA55'
            ],
        );

        $this->assertEquals([
            'color' => '#BADA55',
            'font' => 'Menlo'
        ], $store->all());
    }

    public function test_all_merges_defaults_recursively()
    {
        $store = $this->storeInstance(
            data: [
                'font' => 'Menlo',
                'color' => [
                    'primary' => '#BADA55',
                ]
            ],
            defaults: [
                'color' => [
                    'primary' => 'yellow',
                    'secondary' => 'salmon',
                ]
            ],
        );

        $this->assertEquals([
            'font' => 'Menlo',
            'color' => [
                'primary' => '#BADA55',
                'secondary' => 'salmon',
            ],
        ], $store->all());
    }
}
