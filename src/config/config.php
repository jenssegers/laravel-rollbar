<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Rollbar Environments
    |--------------------------------------------------------------------------
    |
    | Enable automatic error and log reporting for these environments.
    |
    */

    'environments' => array('production'),


    /*
    |--------------------------------------------------------------------------
    | API Access Token
    |--------------------------------------------------------------------------
    |
    | This is your 'post_server_item' access token for the Rollbar API. This
    | token can be found in the 'Project Access Tokens' section in
    | your project settings.
    |
    */

    'token' => '',


    /*
    |--------------------------------------------------------------------------
    | Maximum Error Number
    |--------------------------------------------------------------------------
    |
    | The maximum error number to report. Default: ignore E_STRICT and above.
    |
    */

    'max_errno' => E_USER_NOTICE,

);
