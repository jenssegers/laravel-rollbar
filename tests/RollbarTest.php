<?php

namespace Rollbar\Laravel\Tests;

use Rollbar\Laravel\RollbarServiceProvider;
use Rollbar\Laravel\MonologHandler;
use Rollbar\RollbarLogger;
use Monolog\Logger;
use Mockery;

class RollbarTest extends \Orchestra\Testbench\TestCase
{
    public function setUp()
    {
        $this->access_token = 'B42nHP04s06ov18Dv8X7VI4nVUs6w04X';
        putenv('ROLLBAR_TOKEN=' . $this->access_token);

        parent::setUp();
        
        Mockery::close();
    }

    protected function getPackageProviders($app)
    {
        return [RollbarServiceProvider::class];
    }

    public function testBinding()
    {
        $client = $this->app->make(RollbarLogger::class);
        $this->assertInstanceOf(RollbarLogger::class, $client);

        $handler = $this->app->make(MonologHandler::class);
        $this->assertInstanceOf(MonologHandler::class, $handler);
    }

    public function testIsSingleton()
    {
        $handler1 = $this->app->make(MonologHandler::class);
        $handler2 = $this->app->make(MonologHandler::class);
        $this->assertEquals(spl_object_hash($handler1), spl_object_hash($handler2));
    }

    public function testPassConfiguration()
    {
        $client = $this->app->make(RollbarLogger::class);
        $config = $client->extend([]);
        $this->assertEquals($this->access_token, $config['access_token']);
    }

    public function testCustomConfiguration()
    {
        $this->app->config->set('logging.channels.rollbar.root', '/tmp');
        $this->app->config->set('logging.channels.rollbar.included_errno', E_ERROR);
        $this->app->config->set('logging.channels.rollbar.environment', 'staging');

        $client = $this->app->make(RollbarLogger::class);
        $config = $client->extend([]);
        
        $this->assertEquals('staging', $config['environment']);
        $this->assertEquals('/tmp', $config['root']);
        $this->assertEquals(E_ERROR, $config['included_errno']);
    }

    // public function testAutomaticContext()
    // {
    //     $this->app->session->put('foo', 'bar');
        
    //     $logger = \Mockery::mock('Rollbar\RollbarLogger[log]', [[
    //         'access_token' => $this->access_token,
    //         'environment' => 'testAutomaticContext'
    //     ]]);
    //     $logger->shouldReceive('log')->withArgs(function($args) {
    //         var_dump($args); die();
    //     });
        
    //     $handler = new MonologHandler($logger, \Monolog\Logger::INFO);
    //     $handler->setApp($this->app);
        
    //     $this->app->log->getMonolog()->pushHandler($handler);
        
    //     $this->app->log->info('Test log message');
        
        // var_dump([
        //     'level' => 'info',
        //     'level_name' => 'INFO',
        //     'channel' => 'local',
        //     'datetime' => $time->format('u')
        // ]);
        
        // $handler = new MonologHandler($logger, Logger::INFO);
        // $handler->setApp($this->app);
        
        // $handler->handle([
        //     'level' => Logger::INFO,
        //     'message' => 'Test log message',
        //     'context' => [],
        //     'extra' => [],
        //     'level_name' => 'INFO',
        //     'channel' => 'local',
        //     'datetime' => $time,
        //     'formatted' => 'foo'
        // ]);
        
        // $config = $logger->extend([]);

        // $this->assertEquals([
        //     'session' => ['foo' => 'bar'],
        //     'id'      => $this->app->session->getId(),
        // ], $config['person']);
    // }

    // public function testMergedContext()
    // {
    //     $this->app->session->put('foo', 'bar');
        
    //     $logger = $this->app->make(RollbarLogger::class);
        
    //     $handlerMock = \Mockery::mock(RollbarLogHandler::class, [$logger, $this->app]);
    //     $handlerMock->shouldReceive('log')->passthru();
    //     $this->app[RollbarLogHandler::class] = $handlerMock;
        
    //     $handlerMock->log('info', 'Test log message', [
    //         'tags'   => ['one' => 'two'],
    //         'person' => ['id'  => "1337", 'email' => 'john@doe.com'],
    //     ]);
        
    //     $config = $logger->extend([]);

    //     $this->assertEquals([
    //         'session' => ['foo' => 'bar'],
    //         'id'      => "1337",
    //         'email'   => 'john@doe.com',
    //     ], $config['person']);
    // }

    // public function testLogListener()
    // {
    //     $exception = new \Exception('Testing error handler');

    //     $clientMock = \Mockery::mock(RollbarLogger::class);
        
    //     $clientMock->shouldReceive('log')->times(2);
    //     $clientMock->shouldReceive('log')->times(1)->with('error', $exception, ['foo' => 'bar']);

    //     $handlerMock = \Mockery::mock(RollbarLogHandler::class, [$clientMock, $this->app]);
        
    //     $handlerMock->shouldReceive('log')->passthru();
        
    //     $this->app[RollbarLogHandler::class] = $handlerMock;

    //     $this->app->log->info('hello');
    //     $this->app->log->error('oops');
    //     $this->app->log->error($exception, ['foo' => 'bar']);
    // }

    // public function testErrorLevels1()
    // {
    //     $this->app->config->set('logging.channels.rollbar.level', 'critical');

    //     $clientMock = \Mockery::mock(RollbarLogger::class);
    //     $clientMock->shouldReceive('log')->times(3);
    //     $this->app[RollbarLogger::class] = $clientMock;

    //     $this->app->log->debug('hello');
    //     $this->app->log->info('hello');
    //     $this->app->log->notice('hello');
    //     $this->app->log->warning('hello');
    //     $this->app->log->error('hello');
    //     $this->app->log->critical('hello');
    //     $this->app->log->alert('hello');
    //     $this->app->log->emergency('hello');
    // }

    // public function testErrorLevels2()
    // {
    //     $this->app->config->set('logging.channels.rollbar.level', 'debug');

    //     $clientMock = \Mockery::mock(RollbarLogger::class);
    //     $clientMock->shouldReceive('log')->times(8);
    //     $this->app[RollbarLogger::class] = $clientMock;

    //     $this->app->log->debug('hello');
    //     $this->app->log->info('hello');
    //     $this->app->log->notice('hello');
    //     $this->app->log->warning('hello');
    //     $this->app->log->error('hello');
    //     $this->app->log->critical('hello');
    //     $this->app->log->alert('hello');
    //     $this->app->log->emergency('hello');
    // }

    // public function testErrorLevels3()
    // {
    //     $this->app->config->set('logging.channels.rollbar.level', 'none');

    //     $clientMock = \Mockery::mock(RollbarLogger::class);
    //     $clientMock->shouldReceive('log')->times(0);
    //     $this->app[RollbarLogger::class] = $clientMock;

    //     $this->app->log->debug('hello');
    //     $this->app->log->info('hello');
    //     $this->app->log->notice('hello');
    //     $this->app->log->warning('hello');
    //     $this->app->log->error('hello');
    //     $this->app->log->critical('hello');
    //     $this->app->log->alert('hello');
    //     $this->app->log->emergency('hello');
    // }

    // public function testPersonFunctionIsCalledWhenSessionContainsAtLeastOneItem()
    // {
    //     $this->app->config->set('logging.channels.rollbar.person_fn', function () {
    //         return [
    //             'id' => '123',
    //             'username' => 'joebloggs',
    //         ];
    //     });

    //     $logger = $this->app->make(RollbarLogger::class);

    //     $this->app->session->put('foo', 'bar');

    //     $this->app->log->debug('hello');

    //     $config = $logger->extend([]);

    //     $person = $config['person'];

    //     $this->assertEquals('123', $person['id']);
    //     $this->assertEquals('joebloggs', $person['username']);
    // }
}
