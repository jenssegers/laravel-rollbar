<?php

class ServiceProviderTest extends Orchestra\Testbench\TestCase {

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
        Config::set('rollbar::access_token', $token);

        $rollbar = App::make('rollbar');
        $this->assertEquals($token, $rollbar->access_token);
    }

    public function testDefaultConfiguration()
    {
        $rollbar = App::make('rollbar');
        $this->assertEquals(App::environment(), $rollbar->environment);
        $this->assertEquals(base_path(), $rollbar->root);
        $this->assertEquals(E_USER_NOTICE, $rollbar->max_errno);
        $this->assertEquals('https://api.rollbar.com/api/1/', $rollbar->base_api_url);
    }

    public function testCustomConfiguration()
    {
        Config::set('rollbar::root', '/tmp');
        Config::set('rollbar::max_errno', E_ERROR);
        Config::set('rollbar::environment', 'staging');

        $rollbar = App::make('rollbar');
        $this->assertEquals('staging', $rollbar->environment);
        $this->assertEquals('/tmp', $rollbar->root);
        $this->assertEquals(E_ERROR, $rollbar->max_errno);
        $this->assertEquals('https://api.rollbar.com/api/1/', $rollbar->base_api_url);
    }

    public function testIsSingleton()
    {
        $rollbar1 = App::make('rollbar');
        $rollbar2 = App::make('rollbar');
        $this->assertEquals(spl_object_hash($rollbar1), spl_object_hash($rollbar2));
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

}
