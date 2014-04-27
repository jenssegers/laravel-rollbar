<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Rollbar access token
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
    | User identification
    |--------------------------------------------------------------------------
    |
    | An associative array containing data about the currently-logged in user.
    | Required: id, optional: username, email. All values are strings.
    |
    */

    'person' => array(

    ),


    /*
    |--------------------------------------------------------------------------
    | Maximum error number to report
    |--------------------------------------------------------------------------
    |
    | Default: ignore E_STRICT and above.
    |
    */

    'max_errno' => E_USER_NOTICE,

);
