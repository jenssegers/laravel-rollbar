<?php namespace Jenssegers\Rollbar;

use App;
use Config;
use Exception;
use RollbarNotifier;
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
        $this->package('jenssegers/rollbar');

        // Register error listener
        $this->app->error(function(Exception $exception)
        {
            $rollbar = App::make('rollbar');
            $rollbar->report_exception($exception);
        });

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
                'max_errno' => Config::get('rollbar::max_errno'),
                'person' => Config::get('rollbar::person'),
            );

            return new RollbarNotifier($config);
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
