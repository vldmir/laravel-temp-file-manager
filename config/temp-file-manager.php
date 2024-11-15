<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Temporary Files Directory
    |--------------------------------------------------------------------------
    |
    | This value determines the directory where temporary files will be stored.
    | The path is relative to the storage disk specified below.
    |
    */
    'directory' => 'temp',

    /*
    |--------------------------------------------------------------------------
    | Maximum File Age (Hours)
    |--------------------------------------------------------------------------
    |
    | Files older than this value (in hours) will be automatically cleaned up
    | when running the cleanup command.
    |
    */
    'max_age_hours' => 10,

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | The storage disk where temporary files will be stored. This should be
    | configured in your filesystem.php config file.
    |
    */
    'disk' => 'local',
];
