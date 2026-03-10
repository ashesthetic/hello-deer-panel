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
        public array $cheaperStations,  // array of GasBuddyStation models
        public int   $totalCheaper,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ GasBuddy Alert: ' . $this->totalCheaper . ' stations are cheaper than us',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gasbuddy-price-alert',
        );
    }
}
