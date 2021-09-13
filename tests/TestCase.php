<?php

namespace aspirantzhang\octopusRevision;

use Mockery as m;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $langMock = m::mock('alias:think\facade\Lang');
        $langMock->shouldReceive('get')->andReturnUsing(function (string $name, array $vars = [], string $lang = '') {
            if (!empty($vars)) {
                return $name . ': ' . implode(';', array_map(function ($key, $value) {
                    return $key . '=' . $value;
                }, array_keys($vars), $vars));
            }
            return $name;
        });
        $langMock->shouldReceive('getLangSet')->andReturn('en-us');
    }

    protected function tearDown(): void
    {
    }
}
