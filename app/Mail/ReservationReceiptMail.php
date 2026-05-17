<?php

namespace App\Mail;

use App\Models\EndUserProfile;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Receipt for reservation '.$this->reservation->reservation_code,
        );
    }

    public function content(): Content
    {
        $this->reservation->loadMissing([
            'user',
            'court.location',
            'location',
            'equipment.equipmentType',
            'payments' => fn ($q) => $q->whereIn('status', ['verified', 'refunded'])->orderBy('id'),
        ]);

        return new Content(
            view: 'emails.reservation-receipt',
            with: [
                'reservation' => $this->reservation,
                'profile' => EndUserProfile::query()->where('user_id', $this->reservation->user_id)->first(),
            ],
        );
    }
}
