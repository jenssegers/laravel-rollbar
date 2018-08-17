<?php

namespace Rollbar\Laravel;

use App\Exceptions\Handler as AppHandler;

class ExceptionHandler extends AppHandler
{
    public function report(\Exception $exception)
    {
        \Rollbar\Rollbar::log(\Rollbar\Payload\Level::ERROR, $exception);
        
        parent::report($exception);
    }
}
