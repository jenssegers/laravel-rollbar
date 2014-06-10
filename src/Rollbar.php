<?php namespace Jenssegers\Rollbar;

use Session;
use RollbarNotifier;
use Illuminate\Queue\QueueManager;

class Rollbar extends RollbarNotifier {

    /**
     * The queue manager instance.
     *
     * @var \Illuminate\Queue\QueueManager
     */
    protected $queue;

    /**
     * Constructor.
     */
    public function __construct($config = array(), QueueManager $queue = null)
    {
        parent::__construct($config);

        $this->setQueue($queue);
    }

    /**
     * Set the queue manager instance.
     *
     * @param  \Illuminate\Queue\QueueManager  $queue
     * @return \Jenssegers\Rollbar\Rollbar
     */
    public function setQueue(QueueManager $queue = null)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function send_payload($payload)
    {
        // If we have a QueueInterface instance, we will push the payload as
        // a job to the queue instead of sending it to Rollbar directly.
        if ($this->queue)
        {
            $this->queue->push('Jenssegers\Rollbar\Job', $payload);
        }

        // Otherwise we will just execute the original send_payload method.
        else
        {
            return parent::send_payload($payload);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function build_request_data()
    {
        $request = parent::build_request_data();

        // Add Laravel session data to the request data.
        if ($session = Session::all())
        {
            $session = $this->scrub_request_params($session);

            if (isset($request['session']))
            {
                $request['session'] = array_merge($request['session'], $session);
            }
            else
            {
                $request['session'] = $session;
            }
        }

        return $this->_request_data = $request;
    }

    /**
     * Send data from the queue job.
     *
     * @param  array $data
     * @return void
     */
    public function sendFromJob($payload)
    {
        return parent::send_payload($payload);
    }

    /**
     * Allow camel case methods.
     *
     * @param  string $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this, snake_case($method)), $parameters);
    }

}
