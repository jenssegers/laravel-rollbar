<?php namespace Jenssegers\Rollbar\Facades;

use \Illuminate\Support\Facades\Facade;

class Rollbar extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'rollbar'; }

}
