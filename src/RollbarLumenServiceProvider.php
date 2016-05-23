<?php namespace Jenssegers\Rollbar;

use Jenssegers\Rollbar\RollbarLogHandler;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Monolog\Handler\RollbarHandler;
use Rollbar;
use RollbarNotifier;

class RollbarLumenServiceProvider extends ServiceProvider
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

        // Don't register rollbar if it is not configured.
        if (! getenv('ROLLBAR_TOKEN') and ! $this->app['config']->get('services.rollbar')) {
            return;
        }

        $app = $this->app;

        $app[RollbarNotifier::class] = $app->share(function ($app) {

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

        $app[RollbarLogHandler::class] = $app->share(function ($app) {
            $level = getenv('ROLLBAR_LEVEL') ?: $app['config']->get('services.rollbar.level', 'debug');

            $handler = app(RollbarHandler::class, [$this->app[RollbarNotifier::class], $level]);

            return $handler;
        });

        // Register the fatal error handler.
        register_shutdown_function(function () use ($app) {
            if (isset($app[Rollbar::class])) {
                $app->make(Rollbar::class);
                Rollbar::report_fatal_error();
            }
        });

        // If the Rollbar client was resolved, then there is a possibility that there
        // are unsent error messages in the internal queue, so let's flush them.
        register_shutdown_function(function () use ($app) {
            if (isset($app[Rollbar::class])) {
                $app[Rollbar::class]->flush();
            }
        });
    }

    public function boot()
    {
        $app = $this->app;

        // Listen to log messages.
        $app['log']->pushHandler(
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