<?php

class RollbarTest extends Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Jenssegers\Rollbar\RollbarServiceProvider'];
    }

    public function testBinding()
    {
        $handler = $this->app->make(\Jenssegers\Rollbar\RollbarLogHandler::class);
        $this->assertInstanceOf(\Jenssegers\Rollbar\RollbarLogHandler::class, $handler);
    }
}
