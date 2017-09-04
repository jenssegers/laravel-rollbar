<?php namespace Rollbar\Laravel;

use Rollbar\Laravel\Facades\Rollbar as RollbarFacade;

class RollbarTest extends \Orchestra\Testbench\TestCase
{
    public function setUp()
    {
        $this->access_token = 'B42nHP04s06ov18Dv8X7VI4nVUs6w04X';
        putenv('ROLLBAR_TOKEN=' . $this->access_token);

        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return ['Rollbar\Laravel\RollbarServiceProvider'];
    }

    public function testBinding()
    {
        $client = $this->app->make('Rollbar\RollbarLogger');
        $this->assertInstanceOf('Rollbar\RollbarLogger', $client);

        $handler = $this->app->make('Rollbar\Laravel\RollbarLogHandler');
        $this->assertInstanceOf('Rollbar\Laravel\RollbarLogHandler', $handler);
    }

    public function testIsSingleton()
    {
        $handler1 = $this->app->make('Rollbar\Laravel\RollbarLogHandler');
        $handler2 = $this->app->make('Rollbar\Laravel\RollbarLogHandler');
        $this->assertEquals(spl_object_hash($handler1), spl_object_hash($handler2));
    }

    public function testFacade()
    {
        $client = RollbarFacade::getFacadeRoot();
        $this->assertInstanceOf('Rollbar\Laravel\RollbarLogHandler', $client);
    }

    public function testPassConfiguration()
    {
        $client = $this->app->make('Rollbar\RollbarLogger');
        $config = $client->extend([]);
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
        
        $handlerMock = \Mockery::mock('Rollbar\Laravel\RollbarLogHandler', [$logger, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['Rollbar\Laravel\RollbarLogHandler'] = $handlerMock;
        
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
        
        $handlerMock = \Mockery::mock('Rollbar\Laravel\RollbarLogHandler', [$logger, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['Rollbar\Laravel\RollbarLogHandler'] = $handlerMock;
        
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
        
        $clientMock->shouldReceive('log')->times(2);
        $clientMock->shouldReceive('log')->times(1)->with('error', $exception, ['foo' => 'bar']);

        $handlerMock = \Mockery::mock('Rollbar\Laravel\RollbarLogHandler', [$clientMock, $this->app]);
        
        $handlerMock->shouldReceive('log')->passthru();
        
        $this->app['Rollbar\Laravel\RollbarLogHandler'] = $handlerMock;

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

    public function testPersonFunctionIsCalledWhenSessionContainsAtLeastOneItem()
    {
        $this->app->config->set('services.rollbar.person_fn', function () {
            return [
                'id' => '123',
                'username' => 'joebloggs',
            ];
        });

        $logger = $this->app->make('Rollbar\RollbarLogger');

        $this->app->session->put('foo', 'bar');

        $this->app->log->debug('hello');

        $config = $logger->extend([]);

        $person = $config['person'];

        $this->assertEquals('123', $person['id']);
        $this->assertEquals('joebloggs', $person['username']);
    }
}
