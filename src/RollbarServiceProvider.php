<?php namespace Jenssegers\Rollbar;

use App;
use Config;
use Exception;
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

            Rollbar::init($config, false, false);
            return Rollbar::$instance;
        });
    }

    /**
     * Register error and log listeners.
     *
     * @return void
     */
    protected function registerListeners()
    {
        // Register error listener
        $this->app->error(function(Exception $exception)
        {
            if ( ! in_array(App::environment(), Config::get('rollbar::environments'))) return;

            $rollbar = App::make('rollbar');
            $rollbar->report_exception($exception);
        });

        // Register log listener
        $this->app->log->listen(function($level, $message, $context)
        {
            if ( ! in_array(App::environment(), Config::get('rollbar::environments'))) return;

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
            if ( ! in_array(App::environment(), Config::get('rollbar::environments'))) return;

            $rollbar = App::make('rollbar');
            $rollbar->flush();
        });
    }

}
