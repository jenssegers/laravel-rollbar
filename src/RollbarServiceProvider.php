<?php namespace Rollbar\Laravel;

use InvalidArgumentException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Rollbar\Laravel\RollbarLogHandler;
use Rollbar\RollbarLogger;
use Rollbar\Rollbar;

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
        // Don't boot rollbar if it is not configured.
        if ($this->stop() === true) {
            return;
        }

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

            $app[RollbarLogHandler::class]->log($level, $message, $context);
        });
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        // Don't register rollbar if it is not configured.
        if ($this->stop() === true) {
            return;
        }

        $this->app->singleton(RollbarLogger::class, function ($app) {

            $defaults = [
                'environment'       => $app->environment(),
                'root'              => base_path(),
                'handle_exception'  => true,
                'handle_error'      => true,
                'handle_fatal'      => true,
            ];

            $config = array_merge($defaults, $app['config']->get('logging.channels.rollbar', []));

            $config['access_token'] = static::config('access_token');

            if (empty($config['access_token'])) {
                throw new InvalidArgumentException('Rollbar access token not configured');
            }

            $handleException = (bool) array_pull($config, 'handle_exception');
            $handleError = (bool) array_pull($config, 'handle_error');
            $handleFatal = (bool) array_pull($config, 'handle_fatal');

            Rollbar::init($config, $handleException, $handleError, $handleFatal);

            return Rollbar::logger();
        });

        $this->app->singleton(RollbarLogHandler::class, function ($app) {

            $level = static::config('level', 'debug');

            return new RollbarLogHandler($app[RollbarLogger::class], $app, $level);
        });
    }

    /**
     * Check if we should prevent the service from registering
     *
     * @return boolean
     */
    public function stop() : bool
    {
        $level = static::config('level');

        $token = static::config('token');

        $hasToken = empty($token) === false;

        return $hasToken === false || $level === 'none';
    }

    /**
     * Return a rollbar logging config
     *
     * @param array|string $key
     * @param mixed $default
     * @return mixed
     */
    protected static function config($key = '', $default = null)
    {
        $envKey = 'ROLLBAR_'.strtoupper($key);

        if ($envKey === 'ROLLBAR_ACCESS_TOKEN') {
            $envKey = 'ROLLBAR_TOKEN';
        }

        $logKey = empty($key) ? 'logging.channels.rollbar' : "logging.channels.rollbar.$key";

        return getenv($envKey) ?: Config::get($logKey, $default);
    }
}
