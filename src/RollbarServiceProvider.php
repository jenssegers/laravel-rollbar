<?php

namespace Jenssegers\Rollbar;

use InvalidArgumentException;
use Rollbar;
use RollbarNotifier;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Rollbar\RollbarLogHandler;

class RollbarServiceProvider extends ServiceProvider
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
        $this->registerLibrary();
    }

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->registerLogListener();
    }

    protected function registerLibrary()
    {
        // Don't register rollbar if it is not configured.
        if (! getenv('ROLLBAR_TOKEN') && ! $this->app['config']->get('services.rollbar')) {
            return;
        }

        $this->registerRollbarNotifier();

        $this->registerRollbarLogHandler();

        $this->registerErrorHandlers();
    }

    protected function registerRollbarNotifier()
    {
        $this->app->singleton('RollbarNotifier', function ($app) {
            // Default configuration.
            $defaults = [
                'environment'  => $app->environment(),
                'root'         => base_path(),
            ];

            $config = array_merge($defaults, $app['config']->get('services.rollbar', []));

            $config['access_token'] = getenv('ROLLBAR_TOKEN') ?: $app['config']->get('services.rollbar.access_token');

            if (is_callable($app['auth']->userResolver())) {
                $config['person_fn'] = function () use ($app, $config) {
                    $user = @call_user_func($app['auth']->userResolver());

                    $person = [
                        'id' => $user->id
                    ];

                    if (array_key_exists('person_attributes', $config)) {
                        foreach ($config['person_attributes'] as $name => $value) {
                            $person[(is_string($name) && is_string($value)) ? $name : $value] =
                                array_reduce(explode('.', $value), function ($o, $p) {
                                    return $o->$p;
                                }, $user);
                        }
                    }

                    return $person;
                };
            }

            if (empty($config['access_token'])) {
                throw new InvalidArgumentException('Rollbar access token not configured.');
            }

            Rollbar::$instance = $rollbar = new RollbarNotifier($config);
            return $rollbar;
        });
    }

    protected function registerRollbarLogHandler()
    {
        $this->app->singleton('Jenssegers\Rollbar\RollbarLogHandler', function ($app) {
            $level = getenv('ROLLBAR_LEVEL') ?: $app['config']->get('services.rollbar.level', 'debug');

            return new RollbarLogHandler($app['RollbarNotifier'], $app, $level);
        });
    }

    protected function registerErrorHandlers()
    {
        // Register the fatal error handler.
        register_shutdown_function(function () {
            if (isset($this->app['RollbarNotifier'])) {
                $rollbar = $this->app->make('RollbarNotifier');

                // Rollbar::report_fatal_error();

                $this->app['RollbarNotifier']->flush();
            }
        });
    }

    protected function registerLogListener()
    {
        $this->app['log']->listen(function () {
            $args = func_get_args();

            // Laravel 5.4 returns a MessageLogged instance only.
            if (count($args) == 1) {
                $level   = $args[0]->level;
                $message = $args[0]->message;
                $context = $args[0]->context;
            } else {
                $level   = $args[0];
                $message = $args[1];
                $context = $args[2];
            }

            $this->app['Jenssegers\Rollbar\RollbarLogHandler']->log($level, $message, $context);
        });
    }
}