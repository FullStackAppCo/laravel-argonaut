<?php
namespace Tests;

use ErrorException;
use FullStackAppCo\Argonaut\Drivers\ArrayDriver;
use FullStackAppCo\Argonaut\Drivers\FilesystemDriver;
use FullStackAppCo\Argonaut\Drivers\JsonStoreDriver;
use FullStackAppCo\Argonaut\JsonStoreManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class JsonStoreManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('argonaut.stores.test', []);
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
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('build')->with($config)->once();

        $mock->store('test');

        // This call should use the cached store.
        $mock->store('test');
    }

    public function test_stores_can_be_set()
    {
        Config::set('argonaut.stores.test', ['value' => 'Initial']);
        $manager = new JsonStoreManager;
        $override = new ArrayDriver;

        $this->assertNotSame($override, $manager->store('test'));
        $manager->set('test', $override);
        $this->assertSame($override, $manager->store('test'));
    }
}