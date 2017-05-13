<?php namespace Jenssegers\Rollbar;

use Exception;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use Monolog\Logger as Monolog;
use Psr\Log\AbstractLogger;
use Rollbar\Payload\Level;
use Rollbar\RollbarLogger;

class RollbarLogHandler extends AbstractLogger
{
    /**
     * The rollbar client instance.
     *
     * @var RollbarLogger
     */
    protected $rollbar;

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
    public function __construct(RollbarLogger $rollbar, Application $app, $level = 'debug')
    {
        $this->rollbar = $rollbar;

        $this->app = $app;

        $this->level = $this->parseLevel($level ?: 'debug');
    }

    /**
     * Log a message to Rollbar.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return \Rollbar\Response
     */
    public function log($level, $message, array $context = [])
    {
        // Check if we want to log this message.
        if ($this->parseLevel($level) < $this->level) {
            return;
        }

        $context = $this->addContext($context);

        if ($message instanceof Exception) {
            return $this->rollbar->log(Level::error(), $message, $context);
        } else {
            return $this->rollbar->log($level, $message, $context);
        }
    }

    /**
     * Add Laravel specific information to the context.
     *
     * @param array $context
     * @return array
     */
    protected function addContext(array $context = [])
    {
        // Add session data.
        if ($session = $this->app->session->all()) {
            // Merge person context.
            if (isset($context['person']) and is_array($context['person'])) {
                $this->rollbar->configure(['person' => $context['person']]);
                unset($context['person']);
            }

            // Add user session information.
            $config = $this->rollbar->extend([]);
            $person = isset($config['person']) ? $config['person'] : [];

            $person['session'] = isset($person['session'])
                ? array_merge($session, $person['session'])
                : $person['session'] = $session;

            // User session id as user id if not set.
            if (! isset($person['id'])) {
                $person['id'] = $this->app->session->getId();
            }

            $this->rollbar->configure(['person' => $person]);
        }

        return $context;
    }

    /**
     * Parse the string level into a Monolog constant.
     *
     * @param  string $level
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
