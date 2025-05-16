<?php

namespace Dcblogdev\Junie\Tests;

use Dcblogdev\Junie\JunieServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            JunieServiceProvider::class,
        ];
    }
}
