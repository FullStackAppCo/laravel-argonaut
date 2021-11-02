<?php
namespace Tests\Support;

use ErrorException;
use FullStackAppCo\Argonaut\ServiceProvider;
use FullStackAppCo\Argonaut\Support\Argonaut;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class ArgonautTest extends TestCase
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
        $this->assertInstanceOf(Argonaut::class, Argonaut::build('settings.json'));
    }

    public function testItStoresInDefaultFilesystem()
    {
        Storage::fake();
        $store = Argonaut::build('settings.json')->put('color', 'yellow')->save();

        $this->assertTrue(Storage::exists('settings.json'));
        $this->assertSame('yellow', $store->get('color'));
    }

    public function testItReturnsEmptyArrayWhenNoFile()
    {
        Storage::fake();
        $this->assertEquals([], Argonaut::build('settings.json')->all());
        $this->assertFalse(Storage::exists('settings.json'));
    }

    public function testItUsesDotSyntax()
    {
        Storage::fake();
        $store = Argonaut::build('settings.json');
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
        $store = Argonaut::build('settings.json')
            ->put('foo', 'bar')
            ->forget('foo');

        $this->assertNull($store->get('foo'));
    }

    public function testEmptyDataIsNotPersisted()
    {
        Storage::fake();
        $store = Argonaut::build('settings.json', []);

        $this->assertFalse(Storage::exists('settings.json'));

        $store->put('test', 234)->save();
        $this->assertTrue(Storage::exists('settings.json'));
    }

    public function testDiskCanBeConfigured()
    {
        Storage::fake('local');
        Argonaut::build([
            'path' => 'settings/theme.json',
            'disk' => 'local'
        ])
            ->put('color', 'yellow')
            ->save();

        $this->assertTrue(Storage::disk('local')->exists('settings/theme.json'));
    }

    public function testDiskCanBeOnDemand()
    {
        Storage::fake('local');
        $disk = Storage::build([
            'root' => Storage::disk('local')->path('on-demand'),
            'driver' => 'local'
        ]);
        Argonaut::build([
            'path' => 'settings.json',
            'disk' => $disk,
        ])
            ->put('color', 'yellow')
            ->save();

        $this->assertTrue(Storage::disk('local')->exists('on-demand/settings.json'));
    }

    public function testPath()
    {
        Storage::fake();
        $store = Argonaut::build('path/to/settings.json');

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
        $store = Argonaut::store('theme')->put('colors.primary', 'pink')->save();

        $this->assertTrue(Storage::exists('settings/theme.json'));
        $this->assertSame($store->get('colors.primary'), 'pink');
    }

    public function testUndefinedStoreThrows()
    {
        Storage::fake();
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage("Store 'site' is not configured");
        Argonaut::store('site');
    }

//    public function ()
//    {
//        Storage::fake();
//        $this->expectException(ErrorException::class);
//        $this->expectExceptionMessage("Store 'site' is not configured");
//        Argonaut::store('site');
//    }
}