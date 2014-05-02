<?php namespace Jenssegers\Rollbar;

use App;

class Job {

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire($job, $payload)
    {
        // Get the Rollbar instance.
        $rollbar = App::make('rollbar');

        // Send the data to Sentry.
        $rollbar->sendFromJob($payload);

        // Delete the processed job.
        $job->delete();
    }

}
