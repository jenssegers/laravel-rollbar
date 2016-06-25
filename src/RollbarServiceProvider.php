<?php namespace Jenssegers\Rollbar;

use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;

use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Rollbar;
use RollbarNotifier;

use Monolog\Handler\RollbarHandler;

class RollbarServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $app = $this->app;
        if ($this->app instanceof LaravelApplication) {
            // Listen to log messages.
            $app['log']->listen(function ($level, $message, $context) use ($app) {
                $app['Jenssegers\Rollbar\RollbarLogHandler']->log($level, $message, $context);
            });
        }elseif($this->app instanceof LumenApplication) {
            // Listen to log messages.
            $app['log']->pushHandler(
                app(RollbarLogHandler::class, [
                    $this->app[Rollbar::class]
                ])
            );
        }

    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        if($this->app instanceof LumenApplication) {
            $this->app->configure('services');
        }
        
        // Don't register rollbar if it is not configured.
        if (! getenv('ROLLBAR_TOKEN') and ! $this->app['config']->get('services.rollbar')) {
            return;
        }

        $app = $this->app;

        $this->app['RollbarNotifier'] = $this->app->share(function ($app) {
            // Default configuration.
            $defaults = [
                'environment'  => $app->environment(),
                'root'         => base_path(),
            ];

            $config = array_merge($defaults, $app['config']->get('services.rollbar', []));

            $config['access_token'] = getenv('ROLLBAR_TOKEN') ?: $app['config']->get('services.rollbar.access_token');

            if (empty($config['access_token'])) {
                throw new InvalidArgumentException('Rollbar access token not configured');
            }

            Rollbar::$instance = $rollbar = new RollbarNotifier($config);

            return $rollbar;
        });

        if ($this->app instanceof LaravelApplication) {
            $this->app['Jenssegers\Rollbar\RollbarLogHandler'] = $this->app->share(function ($app) {
                $level = getenv('ROLLBAR_LEVEL') ?: $app['config']->get('services.rollbar.level', 'debug');

                return new RollbarLogHandler($app['RollbarNotifier'], $app, $level);
            });
        }elseif($this->app instanceof LumenApplication) {
            $app[RollbarLogHandler::class] = $app->share(function ($app) {
                $level = getenv('ROLLBAR_LEVEL') ?: $app['config']->get('services.rollbar.level', 'debug');

                $handler = app(RollbarHandler::class, [$this->app[RollbarNotifier::class], $level]);
                return $handler;
            });
        }

        // Register the fatal error handler.
        register_shutdown_function(function () use ($app) {
            if (isset($app['RollbarNotifier'])) {
                $app->make('RollbarNotifier');
                Rollbar::report_fatal_error();
            }
        });

        // If the Rollbar client was resolved, then there is a possibility that there
        // are unsent error messages in the internal queue, so let's flush them.
        register_shutdown_function(function () use ($app) {
            if (isset($app['RollbarNotifier'])) {
                $app['RollbarNotifier']->flush();
            }
        });
    }
}
