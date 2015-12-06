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

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return ['Jenssegers\Rollbar\RollbarServiceProvider'];
    }

    public function testBinding()
    {
        $client = $this->app->make('RollbarNotifier');
        $this->assertInstanceOf('RollbarNotifier', $client);

        $handler = $this->app->make('Jenssegers\Rollbar\RollbarLogHandler');
        $this->assertInstanceOf('Jenssegers\Rollbar\RollbarLogHandler', $handler);
    }

    public function testIsSingleton()
    {
        $handler1 = $this->app->make('Jenssegers\Rollbar\RollbarLogHandler');
        $handler2 = $this->app->make('Jenssegers\Rollbar\RollbarLogHandler');
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
        $client = $this->app->make('RollbarNotifier');
    }

    public function testPassConfiguration()
    {
        $client = $this->app->make('RollbarNotifier');
        $this->assertEquals($this->access_token, $client->access_token);
    }

    public function testCustomConfiguration()
    {
        $this->app->config->set('services.rollbar.root', '/tmp');
        $this->app->config->set('services.rollbar.included_errno', E_ERROR);
        $this->app->config->set('services.rollbar.environment', 'staging');

        $client = $this->app->make('RollbarNotifier');
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
        $this->app['Jenssegers\Rollbar\RollbarLogHandler'] = $handlerMock;

        $handler = $this->app->make('Jenssegers\Rollbar\RollbarLogHandler');
        $handler->log('info', 'Test log message');

        $this->assertEquals([
            'session' => ['foo' => 'bar'],
            'id'      => $this->app->session->getId(),
        ], $clientMock->person);
    }

    public function testMergedContext()
    {
        $this->app->session->set('foo', 'bar');

        $clientMock = Mockery::mock('RollbarNotifier');
        $clientMock->shouldReceive('report_message')->once()->with("Test log message", "info", [
            'tags' => ['one' => 'two'],
        ]);

        $handlerMock = Mockery::mock('Jenssegers\Rollbar\RollbarLogHandler', [$clientMock, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['Jenssegers\Rollbar\RollbarLogHandler'] = $handlerMock;

        $handler = $this->app->make('Jenssegers\Rollbar\RollbarLogHandler');
        $handler->log('info', 'Test log message', [
            'tags'   => ['one' => 'two'],
            'person' => ['id'  => 1337, 'email' => 'john@doe.com'],
        ]);

        $this->assertEquals([
            'session' => ['foo' => 'bar'],
            'id'      => 1337,
            'email'   => 'john@doe.com',
        ], $clientMock->person);
    }

    public function testLogListener()
    {
        $exception = new Exception('Testing error handler');

        $clientMock = Mockery::mock('RollbarNotifier');
        $clientMock->shouldReceive('report_message')->times(2);
        $clientMock->shouldReceive('report_exception')->times(1)->with($exception, null, ['foo' => 'bar']);

        $handlerMock = Mockery::mock('Jenssegers\Rollbar\RollbarLogHandler', [$clientMock, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['Jenssegers\Rollbar\RollbarLogHandler'] = $handlerMock;

        $this->app->log->info('hello');
        $this->app->log->error('oops');
        $this->app->log->error($exception, ['foo' => 'bar']);
    }

    public function testErrorLevels1()
    {
        $this->app->config->set('services.rollbar.level', 'critical');

        $clientMock = Mockery::mock('RollbarNotifier');
        $clientMock->shouldReceive('report_message')->times(3);
        $this->app['RollbarNotifier'] = $clientMock;

        $this->app->log->debug('hello');
        $this->app->log->info('hello');
        $this->app->log->notice('hello');
        $this->app->log->warning('hello');
        $this->app->log->error('hello');
        $this->app->log->critical('hello');
        $this->app->log->alert('hello');
        $this->app->log->emergency('hello');
    }

    public function testErrorLevels2()
    {
        $this->app->config->set('services.rollbar.level', 'debug');

        $clientMock = Mockery::mock('RollbarNotifier');
        $clientMock->shouldReceive('report_message')->times(8);
        $this->app['RollbarNotifier'] = $clientMock;

        $this->app->log->debug('hello');
        $this->app->log->info('hello');
        $this->app->log->notice('hello');
        $this->app->log->warning('hello');
        $this->app->log->error('hello');
        $this->app->log->critical('hello');
        $this->app->log->alert('hello');
        $this->app->log->emergency('hello');
    }

    public function testErrorLevels3()
    {
        $this->app->config->set('services.rollbar.level', 'none');

        $clientMock = Mockery::mock('RollbarNotifier');
        $clientMock->shouldReceive('report_message')->times(0);
        $this->app['RollbarNotifier'] = $clientMock;

        $this->app->log->debug('hello');
        $this->app->log->info('hello');
        $this->app->log->notice('hello');
        $this->app->log->warning('hello');
        $this->app->log->error('hello');
        $this->app->log->critical('hello');
        $this->app->log->alert('hello');
        $this->app->log->emergency('hello');
    }

}
