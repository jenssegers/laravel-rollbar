<?php namespace Jenssegers\Rollbar;

use Exception;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use Monolog\Logger as Monolog;
use Psr\Log\AbstractLogger;
use Rollbar\Payload\Level;
use Rollbar\Rollbar;

class RollbarLogHandler extends AbstractLogger
{
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
     *
     * @param \Illuminate\Foundation\Application $app
     * @param string $level
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Application $app, $level = 'debug')
    {
        $this->app = $app;
        
        $this->level = $this->parseLevel($level ?: 'debug');
    }
    
    /**
     * Parse the string level into a Monolog constant.
     *
     * @param  string $level
     *
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    protected function parseLevel($level)
    {
        if (isset($this->levels[$level])) {
            return $this->levels[$level];
        }
        
        throw new InvalidArgumentException('Invalid log level: '.$level);
    }
    
    /**
     * Log a message to Rollbar.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @throws \InvalidArgumentException
     */
    public function log($level, $message, array $context = [])
    {
        // Check if we want to log this message.
        if ($this->parseLevel($level) < $this->level) {
            return;
        }
        
        $context = $this->addContext($context);
        
        if ($message instanceof Exception) {
            Rollbar::log(Level::error(), $message, $context)->getUuid();
        } else {
            $level = $level ? Level::fromName($level) : Level::error();
            Rollbar::log($level, $message, $context)->getUuid();
        }
    }
    
    /**
     * Add Laravel specific information to the context.
     *
     * @param array $context
     *
     * @return array
     */
    protected function addContext(array $context = [])
    {
        // Add session data.
        if ($session = $this->app->session->all()) {
            $person = [];
            
            // Merge person context.
            if (isset($context['person']) and is_array($context['person'])) {
                $person = $context['person'];
                unset($context['person']);
            }
            
            // Add user session information.
            if (isset($person['session'])) {
                $person['session'] = array_merge($session, $person['session']);
            } else {
                $person['session'] = $session;
            }
            
            // User session id as user id if not set.
            if (!isset($person['id'])) {
                $person['id'] = $this->app->session->getId();
            }
            
            Rollbar::logger()->configure([
                    'person' => $person
                ]);
        }
        
        return $context;
    }
}
