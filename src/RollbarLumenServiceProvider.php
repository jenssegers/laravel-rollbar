<?php

namespace Jenssegers\Rollbar;

use Rollbar;
use RollbarNotifier;
use Monolog\Handler\RollbarHandler;
use Jenssegers\Rollbar\RollbarLogHandler;

class RollbarLumenServiceProvider extends RollbarServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->configure('services');

        $this->registerLibrary();
    }

    protected function registerRollbarLogHandler()
    {
        $this->app[RollbarLogHandler::class] = $this->app->share(function ($app) {
            $level = getenv('ROLLBAR_LEVEL') ?: $app['config']->get('services.rollbar.level', 'debug');

            $handler = app(RollbarHandler::class, [$this->app[RollbarNotifier::class], $level]);
            $handler->getFormatter()->includeStacktraces();

            return $handler;
        });
    }

    protected function registerLogListener()
    {
        $this->app['log']->pushHandler(
            app(RollbarLogHandler::class, [
                $this->app[Rollbar::class]
            ])
        );
    }

    public function provides()
    {
        return [
            RollbarLogHandler::class
        ];
    }
}