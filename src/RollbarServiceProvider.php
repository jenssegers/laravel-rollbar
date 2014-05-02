<?php namespace Jenssegers\Rollbar;

use App;
use Config;
use Exception;
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
        // Fix for PSR-4
        $this->package('jenssegers/rollbar', 'rollbar', realpath(__DIR__));

        // Register listeners
        $this->registerListeners();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('rollbar', function($app)
        {
            // Automatic values
            $automatic = array(
                'environment' => App::environment(),
                'root' => base_path()
            );

            $config = array_merge($automatic, Config::get('rollbar::config'));

            // Create Rollbar instance
            $instance = new Rollbar($config);

            // Prepare Rollbar static class
            \Rollbar::$instance = $instance;

            // Flush Rollbar on shutdown
            register_shutdown_function(array($instance, 'flush'));

            return $instance;
        });
    }

    /**
     * Register error and log listeners.
     *
     * @return void
     */
    protected function registerListeners()
    {
        // Register log listener
        $this->app->log->listen(function($level, $message, $context)
        {
            $rollbar = App::make('rollbar');

            if ($message instanceof Exception)
            {
                $rollbar->report_exception($message);
            }
            else
            {
                $rollbar->report_message($message, $level, $context);
            }
        });

        // Register after filter
        $this->app->after(function()
        {
            $rollbar = App::make('rollbar');
            $rollbar->flush();
        });
    }

}
