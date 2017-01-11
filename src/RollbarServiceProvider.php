<?php namespace Jenssegers\Rollbar;

use Rollbar;
use RollbarNotifier;
use InvalidArgumentException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Guard;
use Jenssegers\Rollbar\RollbarLogHandler;

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
        if (! getenv('ROLLBAR_TOKEN') && ! $this->app['config']->get('services.rollbar')) {
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
        $this->app[RollbarNotifier::class] = $this->app->share(function ($app) {
            // Default configuration.
            $defaults = [
                'environment'  => $app->environment(),
                'root'         => base_path(),
            ];

            $config = array_merge($defaults, $app['config']->get('services.rollbar', []));

            $config['access_token'] = getenv('ROLLBAR_TOKEN') ?: $app['config']->get('services.rollbar.access_token');

            if (is_callable($app['auth']->userResolver())) {
                $config['person_fn'] = function () use ($app, $config) {
                    $user = @call_user_func($app['auth']->userResolver());

                    $person = [
                        'id' => $user->id
                    ];

                    if (array_key_exists('person_attributes', $config)) {
                        foreach ($config['person_attributes'] as $name => $value) {
                            $person[(is_string($name) && is_string($value)) ? $name : $value] =
                                array_reduce(explode('.', $value), function ($o, $p) {
                                    return $o->$p;
                                }, $user);
                        }
                    }

                    return $person;
                };
            }

            if (empty($config['access_token'])) {
                throw new InvalidArgumentException('Rollbar access token not configured.');
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
        register_shutdown_function(function () {
            if (isset($this->app[RollbarNotifier::class])) {
                $rollbar = $this->app->make(RollbarNotifier::class);

                // Rollbar::report_fatal_error();

                $this->app[RollbarNotifier::class]->flush();
            }
        });
    }

    protected function registerLogListener()
    {
        $this->app['log']->listen(function ($level, $message, $context) use ($app) {

            if ($user = \Auth::user()) {
                $context['person'] = [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'email'    => $user->email
                ];
            }

            $app[RollbarLogHandler::class]->log($level, $message, $context);
        });
    }
}