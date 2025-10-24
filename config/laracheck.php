<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data Retention Days
    |--------------------------------------------------------------------------
    |
    | This value determines how many days of historical data (exceptions and
    | outages) should be retained in the database. Older data will be
    | automatically deleted by the data:clean-old command.
    |
    | Default: 365 days (1 year)
    |
    */

    'data_retention_days' => env('DATA_RETENTION_DAYS', 365),

];
