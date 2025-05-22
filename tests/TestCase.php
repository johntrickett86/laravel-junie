<?php

namespace johntrickett86\Junie\Tests;

use johntrickett86\Junie\JunieServiceProvider;
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
