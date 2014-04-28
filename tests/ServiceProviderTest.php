<?php

use Config;

class ServiceProviderTest extends Orchestra\Testbench\TestCase {

    public function setUp()
    {
        parent::setUp();
        Config::set('rollbar::environments', array('testing'));
    }

    public function tearDown()
    {
        Mockery::close();
    }

    protected function getPackageProviders()
    {
        return array('Jenssegers\Rollbar\RollbarServiceProvider');
    }

    public function testBinding()
    {
        $rollbar = App::make('rollbar');
        $this->assertInstanceOf('RollbarNotifier', $rollbar);
    }

    public function testPassConfiguration()
    {
        $token = 'B42nHP04s06ov18Dv8X7VI4nVUs6w04X';
        Config::set('rollbar::token', $token);

        $rollbar = App::make('rollbar');
        $this->assertEquals($token, $rollbar->access_token);
    }

    public function testIsSingleton()
    {
        $rollbar1 = App::make('rollbar');
        $rollbar2 = App::make('rollbar');
        $this->assertEquals(spl_object_hash($rollbar1), spl_object_hash($rollbar2));
    }

    public function testEnvironment()
    {
        $rollbar = App::make('rollbar');
        $this->assertEquals(App::environment(), $rollbar->environment);
        $this->assertEquals(base_path(), $rollbar->root);
        $this->assertEquals(E_USER_NOTICE, $rollbar->max_errno);
    }

    public function testRegisterErrorListener()
    {
        $exception = new Exception('Testing error handler');

        $mock = Mockery::mock('RollbarNotifier');
        $mock->shouldReceive('report_exception')->once()->with($exception);
        $this->app->instance('rollbar', $mock);

        $handler = $this->app->exception;
        $response = (string) $handler->handleException($exception);
    }

    public function testRegisterLogListener()
    {
        $exception = new Exception('Testing error handler');

        $mock = Mockery::mock('RollbarNotifier');
        $mock->shouldReceive('report_message')->once()->with('hello', 'info', array());
        $mock->shouldReceive('report_message')->once()->with('oops', 'error', array('context'));
        $mock->shouldReceive('report_exception')->once()->with($exception);
        $this->app->instance('rollbar', $mock);

        Log::info('hello');
        Log::error('oops', array('context'));
        Log::error($exception);
    }

    public function testFlush()
    {
        $mock = Mockery::mock('RollbarNotifier');
        $mock->shouldReceive('flush')->once();
        $this->app->instance('rollbar', $mock);

        Route::enableFilters();
        Event::fire('router.after');
    }

    public function testEnvironments()
    {
        Config::set('rollbar::environments', array('production', 'local', 'staging'));
        $this->app['env'] = 'local';

        $mock = Mockery::mock('RollbarNotifier');
        $mock->shouldReceive('report_message')->times(1);
        $mock->shouldReceive('report_exception')->times(1);
        $this->app->instance('rollbar', $mock);

        $handler = $this->app->exception;
        $handler->handleException(new Exception('Testing error handler'));
        Log::info('hello');

        // ------

        Config::set('rollbar::environments', array('production', 'local', 'staging'));
        $this->app['env'] = 'testing';

        $mock = Mockery::mock('RollbarNotifier');
        $mock->shouldReceive('report_message')->times(0);
        $mock->shouldReceive('report_exception')->times(0);
        $this->app->instance('rollbar', $mock);

        $handler = $this->app->exception;
        $handler->handleException(new Exception('Testing error handler'));
        Log::info('hello');

        // ------

        Config::set('rollbar::environments', array());
        $this->app['env'] = 'testing';

        $mock = Mockery::mock('RollbarNotifier');
        $mock->shouldReceive('report_message')->times(0);
        $mock->shouldReceive('report_exception')->times(0);
        $this->app->instance('rollbar', $mock);

        $handler = $this->app->exception;
        $handler->handleException(new Exception('Testing error handler'));
        Log::info('hello');
    }

}
