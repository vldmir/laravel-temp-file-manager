<?php

namespace Vldmir\TempFileManager\Facades;

use Illuminate\Support\Facades\Facade;

class TempManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'temp-manager';
    }
}
