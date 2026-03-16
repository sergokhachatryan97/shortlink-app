<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartnerWithdrawalRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $partner,
        public string $availableAmount,
        public string $walletAddress,
        public string $comment
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Partner Withdrawal Request',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.partner-withdrawal-request',
        );
    }
}
