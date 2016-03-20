<?php namespace Jenssegers\Rollbar\Facades;

use Illuminate\Support\Facades\Facade;

class Rollbar extends Facade
{
    /**
     * Get a schema builder instance for the default connection.
     *
     * @return \Jenssegers\Rollbar\RollbarLogHandler
     */
    protected static function getFacadeAccessor()
    {
        return 'Jenssegers\Rollbar\RollbarLogHandler';
    }
}
