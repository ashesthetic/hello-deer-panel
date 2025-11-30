<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LocalMailInterceptorr
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if (app()->environment('local')) {
            $event->message->setTo(['aknath.707+localhd@gmail.com' => 'Local Hello Deer Test']);
        }
    }
}
