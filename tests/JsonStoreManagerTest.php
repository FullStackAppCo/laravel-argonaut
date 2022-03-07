<?php
namespace Tests;

use ErrorException;
use FullStackAppCo\Argonaut\Drivers\FilesystemDriver;
use FullStackAppCo\Argonaut\Drivers\JsonStoreDriver;
use FullStackAppCo\Argonaut\JsonStoreManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Nette\Utils\Json;

class JsonStoreManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('argonaut.stores.test', []);
    }

    public function test_it_uses_dot_syntax()
    {
        $store = app(JsonStoreManager::class)->store('test');
        $store->put('color.primary', '#F00BA9');

        $this->assertSame('#F00BA9', $store->get('color.primary'));
        $this->assertSame('#F00BA9', $store->all()['color']['primary']);
    }

    public function test_it_can_forget_values()
    {
        Storage::fake();
        $store = app(JsonStoreManager::class)->store('test')->put('foo', 'bar');
        $this->assertSame('bar', $store->get('foo'));

        $store->forget('foo');
        $this->assertNull($store->get('foo'));
    }

    public function test_it_uses_configuration()
    {
        Storage::fake('local');
        Config::set('argonaut.stores.test', [
            'path' => 'settings/theme.json',
            'disk' => 'local'
        ]);

        $this->app->bind(JsonStoreDriver::class, function ($app, $args) {
            return $app->make(FilesystemDriver::class, $args);
        });

        (new JsonStoreManager)->store('test')->put('color', 'yellow')->save();

        Storage::disk('local')->assertExists('settings/theme.json');
    }

    public function test_undefined_store_throws()
    {
        Storage::fake();
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage("Store 'site' is not configured");
        (new JsonStoreManager)->store('site');
    }

    public function test_it_uses_static_cache()
    {
        $config = [
            'path' => 'settings.json',
            'driver' => 'local'
        ];
        Config::set('argonaut.stores.test', $config);
        $mock = $this->partialMock(JsonStoreManager::class);
        $mock->shouldReceive('build')->with($config)->once();

        $mock->store('test');
        $mock->store('test');
    }
}