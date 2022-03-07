<?php

namespace Tests\Drivers;

use FullStackAppCo\Argonaut\Drivers\FilesystemDriver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilesystemDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_build()
    {
        $this->assertInstanceOf(FilesystemDriver::class, FilesystemDriver::build([
            'path' => 'test.json',
            'disk' => 'local',
        ]));
    }

    public function test_save()
    {
        (new FilesystemDriver('test.json', Storage::disk('local')))
            ->put('foo', 'bar')
            ->put('baz', [2, 3, true, 'boz'])
            ->save();

        $this->assertSame(
            Storage::disk('local')->get('test.json'),
            json_encode([
                'foo' => 'bar',
                'baz' => [2, 3, true, 'boz'],
            ], JSON_PRETTY_PRINT)
        );
    }

    public function test_read()
    {
        $data = [
            'foo' => 'bar',
            'baz' => [7, 'six', 5, false]
        ];
        Storage::disk('local')->put('settings.json', json_encode($data, JSON_PRETTY_PRINT));

        $this->assertSame(
            $data,
            (new FilesystemDriver('settings.json', Storage::disk('local')))->read()
        );
    }

    public function test_it_returns_empty_array_when_no_file()
    {
        $driver = (new FilesystemDriver('test.json', Storage::disk('local')));
        $this->assertEquals([], $driver->read());
        Storage::assertMissing('settings.json');
    }

    public function test_empty_data_is_not_persisted()
    {
        (new FilesystemDriver('settings.json', Storage::disk('local')))->save();
        Storage::assertMissing('settings.json');
    }

    public function test_path()
    {
        $driver = new FilesystemDriver('path/to/settings.json', Storage::disk());

        $this->assertSame('path/to/settings.json', $driver->path());
        $this->assertSame(Storage::path('path/to/settings.json'), $driver->path($absolute = true));
    }

    public function test_it_forgets_cached_on_save()
    {
        $driver = (new FilesystemDriver('settings.json', Storage::disk('local')))->put('color', 'yellow');

        Cache::shouldReceive('forget')->once()->with($driver->cacheKey());
        $driver->save();
    }

    public function test_it_caches_on_read()
    {
        $driver = (new FilesystemDriver('settings.json', Storage::disk('local')));
        Storage::disk('local')->put('settings.json', $driver->encode([
            'testing' => 123,
        ]));

        Cache::shouldReceive('has')->with($driver->cacheKey())->once()->andReturn(false);
        Cache::shouldReceive('put')->with($driver->cacheKey(), ['testing' => 123])->once();

        $driver->read();
    }

    public function test_it_prefers_cached_data_when_available ()
    {
        $store = new FilesystemDriver('settings.json', Storage::disk('local'));
        Cache::set($store->cacheKey(), ['color' => 'yellow']);

        $this->assertSame('yellow', $store->get('color'));
    }

    public function test_disk_can_be_on_demand()
    {
        $disk = Storage::build([
            'root' => Storage::disk('local')->path('on-demand'),
            'driver' => 'local'
        ]);

        (new FilesystemDriver('settings.json', $disk))->put('color', 'yellow')->save();

        Storage::disk('local')->assertExists('on-demand/settings.json');
    }
}
