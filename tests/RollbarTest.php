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

    public function testIsSingleton()
    {
        $handler1 = $this->app->make(\Jenssegers\Rollbar\RollbarLogHandler::class);
        $handler2 = $this->app->make(\Jenssegers\Rollbar\RollbarLogHandler::class);
        $this->assertEquals(spl_object_hash($handler1), spl_object_hash($handler2));
    }
}
