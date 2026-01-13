<?php

namespace App\Providers;

use App\Models\FuelVolume;
use App\Observers\FuelVolumeObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        FuelVolume::observe(FuelVolumeObserver::class);
        
        if (app()->environment('local')) {
            Event::listen(MessageSending::class, function ($event) {

                // Replace all recipients with your test email
                $event->message->to('aknath.707+localhd@gmail.com');

                // Optional: add original email in subject
                $originalTo = $event->message->getTo();
                if ($originalTo) {
                    $original = implode(', ', array_keys($originalTo));
                    $event->message->subject('[LOCAL: ' . $original . '] ' . $event->message->getSubject());
                }
            });
        }
    }
}
