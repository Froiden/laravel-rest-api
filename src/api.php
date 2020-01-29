<?php

return [

    /**
     * Default number of records to return when no limit is specified
     */
    'defaultLimit' => 10,

    /**
     * Maximum number of records to return in single request. This limit is used
     * when user enters large number in limit parameter of the request
     */
    'maxLimit' => 1000,

    /*
     * Add allow cross origin headers. It is recommended by APIs to allow cross origin
     * requests. But, you can disable it.
     */
    'cors' => true,

    /**
     * Which headers are allowed in CORS requests
     */
    'cors_headers' => ['Authorization', 'Content-Type'],

    /**
     * List of fields that should not be considered while saving a model
     */
    'excludes' => ['_token'],

    /**
     * Prefix for all the routes
     */
    'prefix' => 'api',

    /**
     * Default version for the API. Set null to disable versions
     */
    'default_version' => 'v1'
];
