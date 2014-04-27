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
            $config = array(
                'access_token' => Config::get('rollbar::token'),
                'environment' => App::environment(),
                'root' => base_path(),
                'max_errno' => Config::get('rollbar::max_errno')
            );

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
            if ( ! Config::get('rollbar::enabled')) return;

            $rollbar = App::make('rollbar');
            $rollbar->report_exception($exception);
        });

        // Register log listener
        $this->app->log->listen(function($level, $message, $context)
        {
            if ( ! Config::get('rollbar::enabled')) return;

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
            if ( ! Config::get('rollbar::enabled')) return;

            $rollbar = App::make('rollbar');
            $rollbar->flush();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('rollbar');
    }
}
