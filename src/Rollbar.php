<?php namespace Jenssegers\Rollbar;

use Queue;
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
     * Send data from the queue job.
     *
     * @param  array $data
     * @return void
     */
    public function sendFromJob($payload)
    {
        return parent::send_payload($payload);
    }

}
