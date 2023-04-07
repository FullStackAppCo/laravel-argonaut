<?php
namespace Tests;

use FullStackAppCo\Argonaut\Drivers\ArrayDriver;
use FullStackAppCo\Argonaut\Drivers\FilesystemDriver;
use FullStackAppCo\Argonaut\Drivers\JsonStoreDriver;
use Illuminate\Support\Facades\App;

class ServiceProviderTest extends TestCase
{
    public function test_it_correctly_constructs_ArrayDriver_from_config()
    {
        $config = [
            'path' => 'unused.json',
            'disk' => 'local',
            'defaults' => ['color' => '#BADA55']
        ];

        $driver = App::make(JsonStoreDriver::class, $config);

        $this->assertInstanceOf(ArrayDriver::class, $driver);

        $this->assertSame('#BADA55', $driver->get('color'));
        $this->assertEquals(['color' => '#BADA55'], $driver->all());
    }

    public function test_it_correctly_constructs_FileSystemDriver_from_config()
    {
        $config = [
            'driver' => FilesystemDriver::class,
            'path' => 'unused.json',
            'disk' => 'local',
            'defaults' => ['color' => '#BADA55']
        ];

        $driver = App::make(JsonStoreDriver::class, $config);

        $this->assertInstanceOf(FilesystemDriver::class, $driver);

        $this->assertSame('#BADA55', $driver->get('color'));
        $this->assertEquals(['color' => '#BADA55'], $driver->all());
    }
}