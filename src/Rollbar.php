<?php namespace Jenssegers\Rollbar;

use Queue;
use Session;
use RollbarNotifier;

class Rollbar extends RollbarNotifier {

    /**
     * Constructor.
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    protected function send_payload($payload)
    {
        // Push the job to the queue instead of sending it to Rollbar directly.
        Queue::push('Jenssegers\Rollbar\Job', $payload);
    }

    /**
     * {@inheritdoc}
     */
    protected function build_request_data()
    {
        $request = parent::build_request_data();

        // Add Laravel session data
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
