<?php
namespace Tests\Facades;

use Tests\TestCase;
use FullStackAppCo\Argonaut\Drivers\ArrayDriver;
use FullStackAppCo\Argonaut\Facades\Argonaut;
use Illuminate\Support\Facades\Config;

class ArgonautTest extends TestCase
{
    public function test_fake_returns_a_store()
    {
        Config::set('argonaut.stores.theme', [
            'color' => '#bada55',
        ]);
        $this->assertInstanceOf(ArrayDriver::class, Argonaut::fake('theme'));
        $this->assertSame('#bada55', Argonaut::fake('theme')->get('color'));
    }

    public function test_fake_overrides_named_store()
    {
        Config::set('argonaut.stores.theme', ['color' => 'lime']);

        Argonaut::store('theme')->put('font', '"Comic Sans"')->save();
        $this->assertSame('"Comic Sans"', Argonaut::store('theme')->get('font'));

        $this->assertNull(Argonaut::fake('theme')->get('font'));
        $this->assertSame('lime', Argonaut::store('theme')->get('color'));
    }

    public function test_fake_resets_named_store_state()
    {
        Config::set('argonaut.stores.theme', ['color' => 'lime']);
        $this->assertSame('lime', Argonaut::store('theme')->get('color'));
    }
}
