<?php namespace Rollbar\Laravel;

use Rollbar\Rollbar;
use Rollbar\RollbarLogger;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Rollbar\Laravel\MonologHandler;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class RollbarServiceProvider extends ServiceProvider
{
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

            $handleException = (bool) Arr::pull($config, 'handle_exception');
            $handleError = (bool) Arr::pull($config, 'handle_error');
            $handleFatal = (bool) Arr::pull($config, 'handle_fatal');

            Rollbar::init($config, $handleException, $handleError, $handleFatal);

            return Rollbar::logger();
        });

        $this->app->singleton(MonologHandler::class, function ($app) {

            $level = static::config('level', 'debug');
            
            $handler = new MonologHandler($app[RollbarLogger::class], $level);
            $handler->setApp($app);

            return $handler;
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

        $token = static::config('access_token');

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
