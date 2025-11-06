<?php

namespace App\Mail;

use App\Models\Gallery;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GalleryExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Gallery $gallery,
        public User $client,
        public int $daysRemaining
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Gallery Expires Soon - ' . $this->gallery->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $galleryUrl = route('client.gallery.show', $this->gallery->slug);
        $unsubscribeUrl = route('unsubscribe', ['token' => $this->generateUnsubscribeToken()]);

        return new Content(
            view: 'emails.gallery-expiring',
            with: [
                'gallery' => $this->gallery,
                'daysRemaining' => $this->daysRemaining,
                'galleryUrl' => $galleryUrl,
                'unsubscribeUrl' => $unsubscribeUrl,
            ],
        );
    }

    /**
     * Generate unsubscribe token for user.
     */
    private function generateUnsubscribeToken(): string
    {
        return hash('sha256', $this->client->id . $this->client->email . config('app.key'));
    }
}
