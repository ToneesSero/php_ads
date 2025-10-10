<?php

namespace App\Providers;

use App\Support\UnreadMessageCounter;
use Illuminate\Support\ServiceProvider;

class UnreadMessagesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('unread-messages', function () {
            return new UnreadMessageCounter();
        });
    }
}
