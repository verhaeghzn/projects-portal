<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Session key
    |--------------------------------------------------------------------------
    |
    | The session key used to store the original user id.
    |
    */
    'session_key' => 'impersonated_by',

    /*
    |--------------------------------------------------------------------------
    | Take redirect
    |--------------------------------------------------------------------------
    |
    | Where to redirect after taking an impersonation.
    | Options: URI path, 'back', or a route name.
    |
    */
    'take_redirect_to' => '/admin',

    /*
    |--------------------------------------------------------------------------
    | Leave redirect
    |--------------------------------------------------------------------------
    |
    | Where to redirect after leaving an impersonation.
    | Options: URI path, 'back', or a route name.
    |
    */
    'leave_redirect_to' => '/admin',
];
