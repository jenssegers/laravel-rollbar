<?php namespace Jenssegers\Rollbar;

use Exception;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use Monolog\Logger as Monolog;
use Psr\Log\AbstractLogger;
use RollbarNotifier;

class RollbarLogHandler extends AbstractLogger
{
    /**
     * The rollbar client instance.
     *
     * @var RollbarNotifier
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
    public function __construct(RollbarNotifier $rollbar, Application $app, $level = 'debug')
    {
        $this->rollbar = $rollbar;

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

        if ($message instanceof Exception) {
            $this->rollbar->report_exception($message, null, $context);
        } else {
            $this->rollbar->report_message($message, $level, $context);
        }
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
            if (empty($this->rollbar->person) or ! is_array($this->rollbar->person)) {
                $this->rollbar->person = [];
            }

            // Merge person context.
            if (isset($context['person']) and is_array($context['person'])) {
                $this->rollbar->person = $context['person'];
                unset($context['person']);
            } else {
                if ($this->rollbar->person_fn && is_callable($this->rollbar->person_fn)) {
                    $data = @call_user_func($this->rollbar->person_fn);
                    if (isset($data['id'])) {
                        $this->rollbar->person = call_user_func($this->rollbar->person_fn);
                    }
                }
            }

            // Add user session information.
            if (isset($this->rollbar->person['session'])) {
                $this->rollbar->person['session'] = array_merge($session, $this->rollbar->person['session']);
            } else {
                $this->rollbar->person['session'] = $session;
            }

            // User session id as user id if not set.
            if (! isset($this->rollbar->person['id'])) {
                $this->rollbar->person['id'] = $this->app->session->getId();
            }
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
