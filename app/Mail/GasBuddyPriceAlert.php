<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GasBuddyPriceAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public float $ourPrice,
        public array $cheaperStations  = [],  // stations with lower price than ours
        public int   $totalCheaper    = 0,
        public array $expensiveStations = [], // stations with higher price than ours
        public int   $totalExpensive   = 0,
    ) {}

    public function envelope(): Envelope
    {
        $parts = [];
        if ($this->totalCheaper > 0)  $parts[] = $this->totalCheaper  . ' cheaper';
        if ($this->totalExpensive > 0) $parts[] = $this->totalExpensive . ' more expensive';
        $subject = '⛽ GasBuddy Alert: ' . implode(' · ', $parts) . ' nearby';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gasbuddy-price-alert',
        );
    }
}
