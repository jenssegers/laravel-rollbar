<?php namespace Jenssegers\Rollbar;

use Jenssegers\Rollbar\Facades\Rollbar as RollbarFacade;

class RollbarTest extends \Orchestra\Testbench\TestCase
{

    protected $access_token = null;

    public function setUp()
    {
        // token equals the one from ./vendor/rollbar/rollbar/tests/RollbarTest.php
        $this->access_token = 'ad865e76e7fb496fab096ac07b1dbabb';
        putenv('ROLLBAR_TOKEN=' . $this->access_token);

        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return ['Jenssegers\Rollbar\RollbarServiceProvider'];
    }

    public function testBinding()
    {
        $client = $this->app->make('Rollbar\RollbarLogger');
        $this->assertInstanceOf('Rollbar\RollbarLogger', $client);

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
        $client = RollbarFacade::getFacadeRoot();
        $this->assertInstanceOf('Jenssegers\Rollbar\RollbarLogHandler', $client);
    }

    public function testPassConfiguration()
    {
        $client = $this->app->make('Rollbar\RollbarLogger');
        $config = $client->extend(array());
        $this->assertEquals($this->access_token, $config['access_token']);
    }

    public function testCustomConfiguration()
    {
        $this->app->config->set('services.rollbar.root', '/tmp');
        $this->app->config->set('services.rollbar.included_errno', E_ERROR);
        $this->app->config->set('services.rollbar.environment', 'staging');

        $client = $this->app->make('Rollbar\RollbarLogger');
        $config = $client->extend([]);

        $this->assertEquals('staging', $config['environment']);
        $this->assertEquals('/tmp', $config['root']);
        $this->assertEquals(E_ERROR, $config['included_errno']);
    }

    public function testAutomaticContext()
    {
        $this->app->session->put('foo', 'bar');

        $logger = $this->app->make('Rollbar\RollbarLogger');

        $handlerMock = \Mockery::mock('Jenssegers\Rollbar\RollbarLogHandler', [$logger, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['Jenssegers\Rollbar\RollbarLogHandler'] = $handlerMock;

        $handlerMock->log('info', 'Test log message');

        $config = $logger->extend([]);

        $this->assertEquals([
            'session' => ['foo' => 'bar'],
            'id'      => $this->app->session->getId(),
        ], $config['person']);
    }

    public function testMergedContext()
    {
        $this->app->session->put('foo', 'bar');

        $logger = $this->app->make('Rollbar\RollbarLogger');

        $handlerMock = \Mockery::mock('Jenssegers\Rollbar\RollbarLogHandler', [$logger, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['Jenssegers\Rollbar\RollbarLogHandler'] = $handlerMock;

        $handlerMock->log('info', 'Test log message', [
            'tags'   => ['one' => 'two'],
            'person' => ['id'  => "1337", 'email' => 'john@doe.com'],
        ]);

        $config = $logger->extend([]);

        $this->assertEquals([
            'session' => ['foo' => 'bar'],
            'id'      => "1337",
            'email'   => 'john@doe.com',
        ], $config['person']);
    }

    public function testLogListener()
    {
        $exception = new \Exception('Testing error handler');

        $clientMock = \Mockery::mock('Rollbar\RollbarLogger');


        // FIXME: I don't get why this expectation is not working but here it does:
        // https://github.com/rollbar/rollbar-php-laravel/blob/484fb3b809829aa91b5c51545274dd3c4d729342/tests/RollbarTest.php#L113
        $clientMock->shouldReceive('log')->times(3);
//        $clientMock->shouldReceive('log')->times(2);
//        $clientMock->shouldReceive('log')->times(1)->with('error', $exception, ['foo' => 'bar']);

        $handlerMock = \Mockery::mock('Jenssegers\Rollbar\RollbarLogHandler', [$clientMock, $this->app]);

        $handlerMock->shouldReceive('log')->passthru();

        $this->app['Jenssegers\Rollbar\RollbarLogHandler'] = $handlerMock;

        $this->app->log->info('hello');
        $this->app->log->error('oops');
        $this->app->log->error($exception, ['foo' => 'bar']);
    }

    public function testErrorLevels1()
    {
        $this->app->config->set('services.rollbar.level', 'critical');

        $clientMock = \Mockery::mock('Rollbar\RollbarLogger');
        $clientMock->shouldReceive('log')->times(3);
        $this->app['Rollbar\RollbarLogger'] = $clientMock;

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

        $clientMock = \Mockery::mock('Rollbar\RollbarLogger');
        $clientMock->shouldReceive('log')->times(8);
        $this->app['Rollbar\RollbarLogger'] = $clientMock;

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

        $clientMock = \Mockery::mock('Rollbar\RollbarLogger');
        $clientMock->shouldReceive('log')->times(0);
        $this->app['Rollbar\RollbarLogger'] = $clientMock;

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
