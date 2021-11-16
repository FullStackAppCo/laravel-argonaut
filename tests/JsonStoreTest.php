<?php
namespace Tests;

use ErrorException;
use FullStackAppCo\Argonaut\ServiceProvider;
use FullStackAppCo\Argonaut\JsonStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;

class JsonStoreTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class
        ];
    }

    protected function unsetTesting(JsonStore $store): JsonStore
    {
        $property = new ReflectionProperty(JsonStore::class, 'testing');
        $property->setAccessible(true);
        $property->setValue($store, false);

        return $store;
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testBuildCreatesInstance()
    {
        $this->assertInstanceOf(JsonStore::class, JsonStore::build('settings.json'));
    }

    public function testItStoresInDefaultFilesystem()
    {
        Storage::fake();
        $store = (new JsonStore('settings.json', Storage::disk()))->put('color', 'yellow')->save();

        Storage::assertExists('settings.json');
        $this->assertSame('yellow', $store->get('color'));
    }

    public function testItReturnsEmptyArrayWhenNoFile()
    {
        Storage::fake();
        $this->assertEquals([], (new JsonStore('settings.json', Storage::disk()))->all());
        Storage::assertMissing('settings.json');
    }

    public function testItUsesDotSyntax()
    {
        Storage::fake();
        $store = new JsonStore('settings.json', Storage::disk());
        $store->put('color.primary', '#F00BA9')->save();

        $this->assertEquals([
            'color' => [
                'primary' => '#F00BA9',
            ]
        ], json_decode(Storage::get('settings.json'), true));
        $this->assertSame('#F00BA9', $store->get('color.primary'));
    }

    public function testItCanForgetValues()
    {
        Storage::fake();
        $store = (new JsonStore('settings.json', Storage::disk()))
            ->put('foo', 'bar')
            ->forget('foo');

        $this->assertNull($store->get('foo'));
    }

    public function testEmptyDataIsNotPersisted()
    {
        Storage::fake();
        $store = (new JsonStore('settings.json', Storage::disk()))->save();

        Storage::assertMissing('settings.json');

        $store->put('test', 234)->save();
        Storage::assertExists('settings.json');
    }

    public function testDiskCanBeConfigured()
    {
        Storage::fake('local');
        $store = JsonStore::build([
            'path' => 'settings/theme.json',
            'disk' => 'local'
        ])->put('color', 'yellow');

        $this->unsetTesting($store)->save();

        Storage::disk('local')->assertExists('settings/theme.json');
    }

    public function testDiskCanBeOnDemand()
    {
        Storage::fake('local');
        $disk = Storage::build([
            'root' => Storage::disk('local')->path('on-demand'),
            'driver' => 'local'
        ]);
        $store = JsonStore::build([
            'path' => 'settings.json',
            'disk' => $disk,
        ])->put('color', 'yellow');

        $this->unsetTesting($store)->save();

        Storage::disk('local')->assertExists('on-demand/settings.json');
    }

    public function testPath()
    {
        Storage::fake();
        $store = new JsonStore('path/to/settings.json', Storage::disk());

        $this->assertSame('path/to/settings.json', $store->path());
        $this->assertSame(Storage::path('path/to/settings.json'), $store->path($absolute = true));
    }

    public function testStoreUsesConfigFile()
    {
        Storage::fake();
        Config::set('argonaut', [
           'stores' => [
               'theme' => [
                   'disk' => 'local',
                   'path' => 'settings/theme.json',
               ],
           ],
        ]);
        $store = JsonStore::store('theme')->put('colors.primary', 'pink');
        $this->unsetTesting($store)->save();

        Storage::assertExists('settings/theme.json');
        $this->assertSame($store->get('colors.primary'), 'pink');
    }

    public function testUndefinedStoreThrows()
    {
        Storage::fake();
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage("Store 'site' is not configured");
        JsonStore::store('site');
    }

    public function testItDoesNotPersistInTesting()
    {
        Storage::fake();
        (new JsonStore('settings.json', Storage::disk()))
            ->testing()
            ->put('foo', 'bar')
            ->save();

        Storage::assertMissing('settings.json');
    }

    public function testItCachesOnSave ()
    {
        Storage::fake();
        $store = JsonStore::build('settings.json')->put('color', 'yellow');
        $key = 'argonaut:' . md5($store->path(true));

        $this->assertFalse(Cache::has($key));
        $this->unsetTesting($store)->save();
        $this->assertTrue(Cache::has($key));
        $this->assertSame(['color' => 'yellow'], json_decode(Cache::get($key), $associative = true));
    }

    public function testEmptyDataClearsCache ()
    {
        Storage::fake();
        $store = $this->unsetTesting(JsonStore::build('settings.json'));

        $key = 'argonaut:' . md5($store->path(true));
        Cache::put($key, 'This data will be purged');

        $this->assertSame(Cache::get($key), 'This data will be purged');
        $store->save();
        $this->assertFalse(Cache::has($key));
    }

    public function testItUsesCachedDataWhenAvailable ()
    {
        Storage::fake();
        $store = new JsonStore('settings.json', Storage::disk());

        $key = 'argonaut:' . md5($store->path(true));
        Cache::set($key, json_encode(['color' => 'yellow']));

        $this->assertSame('yellow', $store->get('color'));
    }
}