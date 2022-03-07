<?php

namespace Tests\Drivers;

use FullStackAppCo\Argonaut\Drivers\ArrayDriver;
use Tests\TestCase;

class ArrayDriverTest extends TestCase
{
    public function test_save()
    {
        $data = [
            'foo' => 'bar',
            'baz' => [1, 5, true, 'testing'],
        ];
        $this->assertSame($data, (new ArrayDriver($data))->save()->collection->toArray());
    }

    public function test_read()
    {
        $data = [
            'foo' => 'bar',
            'baz' => [1, 5, true, 'testing'],
        ];
        $this->assertSame($data, (new ArrayDriver($data))->read());
    }
}
