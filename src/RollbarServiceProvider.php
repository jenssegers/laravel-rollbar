<?php namespace Jenssegers\Rollbar;

use InvalidArgumentException;
use RollbarNotifier;
use Rollbar;
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
        $app['log']->listen(function ($level, $message, $context) use ($app)
        {
            $app['rollbar.handler']->log($level, $message, $context);
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

        $this->app['rollbar.client'] = $this->app->share(function ($app)
        {
            $config = $app['config']->get('services.rollbar');

            if (empty($config['access_token']))
            {
                throw new InvalidArgumentException('Rollbar access token not configured');
            }

            Rollbar::$instance = $rollbar = new RollbarNotifier($config);

            return $rollbar;
        });

        $this->app['rollbar.handler'] = $this->app->share(function ($app)
        {
            $client = $app['rollbar.client'];

            $level = $app['config']->get('services.rollbar.level', 'debug');

            return new RollbarLogHandler($client, $app, $level);
        });
        
        $this->app->singleton('command.rollbar.deploynotify', function($app)
        {
            $config = $app['config']->get('services.rollbar');
            
            return new RollbarDeployNotify($config);
        });
        
        $this->commands('command.rollbar.deploynotify');


        // If the Rollbar client was resolved, then there is a possibility that there
        // are unsent error messages in the internal queue, so let's flush them.
        register_shutdown_function(function () use ($app)
        {
            if (isset($app['rollbar.client']))
            {
                $app['rollbar.client']->flush();
            }
        });

        // Register the fatal error handler.
        register_shutdown_function(function () use ($app)
        {
            if (isset($app['rollbar.client']))
            {
                $app->make('rollbar.client');

                Rollbar::report_fatal_error();
            }
        });
    }
    
    public function provides()
    {
        return [
            'command.rollbar.deploynotify',
        ];
    }
}
