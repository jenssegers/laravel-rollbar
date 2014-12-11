<?php

class RollbarTest extends Orchestra\Testbench\TestCase {

    public function setUp()
    {
        parent::setUp();

        $this->access_token = 'B42nHP04s06ov18Dv8X7VI4nVUs6w04X';
        $this->app->config->set('services.rollbar.access_token', $this->access_token);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    protected function getPackageProviders()
    {
        return ['Jenssegers\Rollbar\RollbarServiceProvider'];
    }

    public function testBinding()
    {
        $client = $this->app->make('rollbar.client');
        $this->assertInstanceOf('RollbarNotifier', $client);

        $handler = $this->app->make('rollbar.handler');
        $this->assertInstanceOf('Jenssegers\Rollbar\RollbarLogHandler', $handler);
    }

    public function testIsSingleton()
    {
        $handler1 = $this->app->make('rollbar.handler');
        $handler2 = $this->app->make('rollbar.handler');
        $this->assertEquals(spl_object_hash($handler1), spl_object_hash($handler2));
    }

    public function testFacade()
    {
        $client = Rollbar::$instance;
        $this->assertInstanceOf('RollbarNotifier', $client);
    }

    public function testNoConfiguration()
    {
        $this->setExpectedException('InvalidArgumentException');

        $this->app->config->set('services.rollbar.access_token', null);
        $client = $this->app->make('rollbar.client');
    }

    public function testPassConfiguration()
    {
        $client = $this->app->make('rollbar.client');
        $this->assertEquals($this->access_token, $client->access_token);
    }

    public function testCustomConfiguration()
    {
        $this->app->config->set('services.rollbar.root', '/tmp');
        $this->app->config->set('services.rollbar.included_errno', E_ERROR);
        $this->app->config->set('services.rollbar.environment', 'staging');

        $client = $this->app->make('rollbar.client');
        $this->assertEquals('staging', $client->environment);
        $this->assertEquals('/tmp', $client->root);
        $this->assertEquals(E_ERROR, $client->included_errno);
    }

    public function testAutomaticContext()
    {
        $this->app->session->set('foo', 'bar');

        $clientMock = Mockery::mock('RollbarNotifier');
        $clientMock->shouldReceive('report_message')->once()->with("Test log message", "info", []);

        $handlerMock = Mockery::mock('Jenssegers\Rollbar\RollbarLogHandler', [$clientMock, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['rollbar.handler'] = $handlerMock;

        $handler = $this->app->make('rollbar.handler');
        $handler->log('info', 'Test log message');

        $this->assertEquals([
            'session' => ['foo' => 'bar'],
            'id' => $this->app->session->getId()
        ], $clientMock->person);
    }

    public function testMergedContext()
    {
        $this->app->session->set('foo', 'bar');

        $clientMock = Mockery::mock('RollbarNotifier');
        $clientMock->shouldReceive('report_message')->once()->with("Test log message", "info", [
            'tags' => ['one' => 'two']
        ]);

        $handlerMock = Mockery::mock('Jenssegers\Rollbar\RollbarLogHandler', [$clientMock, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['rollbar.handler'] = $handlerMock;

        $handler = $this->app->make('rollbar.handler');
        $handler->log('info', 'Test log message', [
            'tags' => ['one' => 'two'],
            'person' => ['id' => 1337, 'email' => 'john@doe.com']
        ]);

        $this->assertEquals([
            'session' => ['foo' => 'bar'],
            'id' => 1337,
            'email' => 'john@doe.com'
        ], $clientMock->person);
    }

    public function testLogListener()
    {
        $exception = new Exception('Testing error handler');

        $clientMock = Mockery::mock('RollbarNotifier');
        $clientMock->shouldReceive('report_message')->times(2);
        $clientMock->shouldReceive('report_exception')->times(1)->with($exception);

        $handlerMock = Mockery::mock('Jenssegers\Rollbar\RollbarLogHandler', [$clientMock, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['rollbar.handler'] = $handlerMock;

        $this->app->log->info('hello');
        $this->app->log->error('oops');
        $this->app->log->error($exception);
    }

    public function testBelowLevel()
    {
        $this->app->config->set('services.rollbar.level', 'error');

        $clientMock = Mockery::mock('RollbarNotifier');
        $clientMock->shouldReceive('report_message')->times(0);
        $this->app['rollbar.client'] = $clientMock;

        $this->app->log->info('hello');
        $this->app->log->debug('hello');
        $this->app->log->notice('hello');
        $this->app->log->warning('hello');
    }

    public function testAboveLevel()
    {
        $this->app->config->set('services.rollbar.level', 'error');

        $clientMock = Mockery::mock('RollbarNotifier');
        $clientMock->shouldReceive('report_message')->times(4);
        $this->app['rollbar.client'] = $clientMock;

        $this->app->log->error('hello');
        $this->app->log->critical('hello');
        $this->app->log->alert('hello');
        $this->app->log->emergency('hello');
    }

}
