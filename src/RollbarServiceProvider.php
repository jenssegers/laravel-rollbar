<?php namespace Jenssegers\Rollbar;

use Illuminate\Support\ServiceProvider;
use Jenssegers\Rollbar\RollbarLogHandler;
use InvalidArgumentException;
use Rollbar;
use RollbarNotifier;

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
        // Don't register rollbar if it is not configured.
        if (! getenv('ROLLBAR_TOKEN') and ! $this->app['config']->get('services.rollbar')) {
            return;
        }

        $this->registerRollbarNotifier();

        $this->registerRollbarLogHandler();

        $this->registerErrorHandlers();
    }

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->registerLogListener();
    }

    protected function registerRollbarNotifier()
    {
        $app[RollbarNotifier::class] = $this->app->share(function ($app) {
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
    }

    protected function registerRollbarLogHandler()
    {
        $this->app[RollbarLogHandler::class] = $this->app->share(function ($app) {
            $level = getenv('ROLLBAR_LEVEL') ?: $app['config']->get('services.rollbar.level', 'debug');

            return new RollbarLogHandler($app[RollbarNotifier::class], $app, $level);
        });
    }

    protected function registerErrorHandlers()
    {
        // Register the fatal error handler.
        register_shutdown_function(function () use ($app) {
            if (isset($this->app[RollbarNotifier::class])) {
                $this->app->make(RollbarNotifier::class);
                Rollbar::report_fatal_error();
            }
        });

        // If the Rollbar client was resolved, then there is a possibility that there
        // are unsent error messages in the internal queue, so let's flush them.
        register_shutdown_function(function () use ($app) {
            if (isset($this->app[RollbarNotifier::class])) {
                $this->app[RollbarNotifier::class]->flush();
            }
        });
    }

    protected function registerLogListener()
    {
        $this->app['log']->listen(function ($level, $message, $context) use ($app) {
            $app[RollbarLogHandler::class]->log($level, $message, $context);
        });
    }
}
