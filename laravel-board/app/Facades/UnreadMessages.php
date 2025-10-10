<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static int count()
 */
class UnreadMessages extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'unread-messages';
    }
}
