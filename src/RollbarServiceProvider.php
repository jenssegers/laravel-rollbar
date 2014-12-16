<?php namespace Jenssegers\Rollbar;

use Exception, InvalidArgumentException;
use RollbarNotifier, Rollbar;
use Illuminate\Support\ServiceProvider;

class RollbarServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $app = $this->app;

        // Listen to log messages.
        $app['log']->listen(function($level, $message, $context) use ($app)
        {
            $app['rollbar.handler']->log($level, $message, $context);
        });

        // Register Laravel shutdown function
        $this->app->shutdown(function() use ($app)
        {
            $app['rollbar.client']->flush();
        });

        // Register PHP shutdown function
        register_shutdown_function(function() use ($app)
        {
            $app['rollbar.client']->flush();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $app = $this->app;

        $this->app['rollbar.client'] = $this->app->share(function($app)
        {
            $config = $app['config']->get('services.rollbar');

            if (empty($config['access_token']))
            {
                throw new InvalidArgumentException('Rollbar access token not configured');
            }

            Rollbar::$instance = $rollbar = new RollbarNotifier($config);

            return $rollbar;
        });

        $this->app['rollbar.handler'] = $this->app->share(function($app)
        {
            $client = $app['rollbar.client'];

            $level = $app['config']->get('services.rollbar.level', 'debug');

            return new RollbarLogHandler($client, $app, $level);
        });
    }

}
