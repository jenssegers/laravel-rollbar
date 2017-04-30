<?php namespace Rollbar\Laravel;

use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Rollbar\Rollbar;
use Rollbar\Laravel\RollbarLogHandler;

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

        // Listen to log messages.
        $app['log']->listen(function () use ($app) {
            $args = func_get_args();

            // Laravel 5.4 returns a MessageLogged instance only
            if (count($args) == 1) {
                $level = $args[0]->level;
                $message = $args[0]->message;
                $context = $args[0]->context;
            } else {
                $level = $args[0];
                $message = $args[1];
                $context = $args[2];
            }

            $app['Rollbar\Laravel\RollbarLogHandler']->log($level, $message, $context);
        });
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        // Don't register rollbar if it is not configured.
        if (! getenv('ROLLBAR_TOKEN') and ! $this->app['config']->get('services.rollbar')) {
            return;
        }

        $app = $this->app;

        $this->app->singleton('Rollbar\RollbarLogger', function ($app) {
            
            $defaults = [
                'environment'  => $app->environment(),
                'root'         => base_path(),
            ];
            $config = array_merge($defaults, $app['config']->get('services.rollbar', []));
            $config['access_token'] = getenv('ROLLBAR_TOKEN') ?: $app['config']->get('services.rollbar.access_token');
            
            if (empty($config['access_token'])) {
                throw new InvalidArgumentException('Rollbar access token not configured');
            }
            
            \Rollbar\Rollbar::init($config);
            
            return Rollbar::logger();
        });

        $this->app->singleton('Rollbar\Laravel\RollbarLogHandler', function ($app) {
            
            $level = getenv('ROLLBAR_LEVEL') ?: $app['config']->get('services.rollbar.level', 'debug');

            return new RollbarLogHandler($app['Rollbar\RollbarLogger'], $app, $level);
        });
    }
}
