<?php

return [
    /**
     * Allowed value are 'basic' (for Basic Auth) and 'bearer' (for Bearer Token Auth)
     * The value 'basic' can be considered the default one
     */
    'auth_type' => 'bearer',
    
    // Config values for JWTs
    'jwt' => [
        'secret' => 'secret'
    ]
];
