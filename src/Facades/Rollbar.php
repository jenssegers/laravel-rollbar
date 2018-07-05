<?php namespace Rollbar\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Rollbar\Laravel\RollbarLogHandler;

class Rollbar extends Facade
{
    /**
     * Get a schema builder instance for the default connection.
     *
     * @return \Rollbar\Laravel\RollbarLogHandler
     */
    protected static function getFacadeAccessor()
    {
        return RollbarLogHandler::class;
    }
}
