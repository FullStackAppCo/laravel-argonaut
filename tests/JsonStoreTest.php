<?php
namespace Tests;

use ErrorException;
use FullStackAppCo\Argonaut\ServiceProvider;
use FullStackAppCo\Argonaut\JsonStore;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class JsonStoreTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class
        ];
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
        $store = JsonStore::build('settings.json')->put('color', 'yellow')->save();

        $this->assertTrue(Storage::exists('settings.json'));
        $this->assertSame('yellow', $store->get('color'));
    }

    public function testItReturnsEmptyArrayWhenNoFile()
    {
        Storage::fake();
        $this->assertEquals([], JsonStore::build('settings.json')->all());
        Storage::assertMissing('settings.json');
    }

    public function testItUsesDotSyntax()
    {
        Storage::fake();
        $store = JsonStore::build('settings.json');
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
        $store = JsonStore::build('settings.json')
            ->put('foo', 'bar')
            ->forget('foo');

        $this->assertNull($store->get('foo'));
    }

    public function testEmptyDataIsNotPersisted()
    {
        Storage::fake();
        $store = JsonStore::build('settings.json', []);

        $this->assertFalse(Storage::exists('settings.json'));

        $store->put('test', 234)->save();
        Storage::assertExists('settings.json');
    }

    public function testDiskCanBeConfigured()
    {
        Storage::fake('local');
        JsonStore::build([
            'path' => 'settings/theme.json',
            'disk' => 'local'
        ])
            ->put('color', 'yellow')
            ->save();

        Storage::disk('local')->assertExists('settings/theme.json');
    }

    public function testDiskCanBeOnDemand()
    {
        Storage::fake('local');
        $disk = Storage::build([
            'root' => Storage::disk('local')->path('on-demand'),
            'driver' => 'local'
        ]);
        JsonStore::build([
            'path' => 'settings.json',
            'disk' => $disk,
        ])
            ->put('color', 'yellow')
            ->save();

        Storage::disk('local')->assertExists('on-demand/settings.json');
    }

    public function testPath()
    {
        Storage::fake();
        $store = JsonStore::build('path/to/settings.json');

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
        $store = JsonStore::store('theme')->put('colors.primary', 'pink')->save();

        $this->assertTrue(Storage::exists('settings/theme.json'));
        $this->assertSame($store->get('colors.primary'), 'pink');
    }

    public function testUndefinedStoreThrows()
    {
        Storage::fake();
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage("Store 'site' is not configured");
        JsonStore::store('site');
    }

//    public function testItCachesStore ()
//    {
//        Storage::fake();
//        $store = JsonFile::build('settings.json')->put('color', 'yellow')->save();
//        $store->put('theme', 'dark')->save();
//
//        $this->assertFalse(Cache::has('argonaut:'))
//    }
}