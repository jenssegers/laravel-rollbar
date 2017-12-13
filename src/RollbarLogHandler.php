<?php namespace Rollbar\Laravel;

use Exception;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use Monolog\Logger as Monolog;
use Psr\Log\AbstractLogger;
use Rollbar\RollbarLogger;

class RollbarLogHandler extends AbstractLogger
{
    /**
     * The rollbar client instance.
     *
     * @var logger
     */
    protected $logger;

    /**
     * The Laravel application.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The minimum log level at which messages are sent to Rollbar.
     *
     * @var string
     */
    protected $level;

    /**
     * The Log levels.
     *
     * @var array
     */
    protected $levels = [
        'debug'     => Monolog::DEBUG,
        'info'      => Monolog::INFO,
        'notice'    => Monolog::NOTICE,
        'warning'   => Monolog::WARNING,
        'error'     => Monolog::ERROR,
        'critical'  => Monolog::CRITICAL,
        'alert'     => Monolog::ALERT,
        'emergency' => Monolog::EMERGENCY,
        'none'      => 1000,
    ];

    /**
     * Constructor.
     */
    public function __construct(RollbarLogger $logger, Application $app, $level = 'debug')
    {
        $this->logger = $logger;

        $this->app = $app;

        $this->level = $this->parseLevel($level ?: 'debug');
    }

    /**
     * Log a message to Rollbar.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = [])
    {
        // Check if we want to log this message.
        if ($this->parseLevel($level) < $this->level) {
            return;
        }

        $context = $this->addContext($context);

        // Workaround Laravel 5.5 moving exception from message into context
        if (! empty($context['exception']) && is_string($message)) {
            $message = $context['exception'];
        }

        return $this->logger->log($level, $message, $context);
    }

    /**
     * Add Laravel specific information to the context.
     *
     * @param array $context
     */
    protected function addContext(array $context = [])
    {
        // Add session data.
        if ($session = $this->app->session->all()) {
            $config = $this->logger->extend([]);

            if (empty($config['person']) or ! is_array($config['person'])) {
                $person = [];
            } else {
                $person = $config['person'];
            }

            // Merge person context.
            if (isset($context['person']) and is_array($context['person'])) {
                $person = $context['person'];
                unset($context['person']);
            } else {
                if (isset($config['person_fn']) && is_callable($config['person_fn'])) {
                    $data = @call_user_func($config['person_fn']);
                    if (isset($data['id'])) {
                        $person = call_user_func($config['person_fn']);
                    }
                }
            }

            // Add user session information.
            if (isset($person['session'])) {
                $person['session'] = array_merge($session, $person['session']);
            } else {
                $person['session'] = $session;
            }

            // User session id as user id if not set.
            if (! isset($person['id'])) {
                $person['id'] = $this->app->session->getId();
            }
                
            $this->logger->configure(['person' => $person]);
        }

        return $context;
    }

    /**
     * Parse the string level into a Monolog constant.
     *
     * @param  string  $level
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    protected function parseLevel($level)
    {
        if (isset($this->levels[$level])) {
            return $this->levels[$level];
        }

        throw new InvalidArgumentException('Invalid log level: ' . $level);
    }
}
