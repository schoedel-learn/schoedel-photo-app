<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order,
        public bool $sendToPhotographer = false
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->sendToPhotographer
            ? 'New Order Received - #' . $this->order->order_number
            : 'Order Confirmation - #' . $this->order->order_number;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $orderUrl = route('orders.show', $this->order);
        $unsubscribeUrl = $this->sendToPhotographer 
            ? null 
            : route('unsubscribe', ['token' => $this->generateUnsubscribeToken()]);

        return new Content(
            view: 'emails.order-confirmation',
            with: [
                'order' => $this->order,
                'orderUrl' => $orderUrl,
                'sendToPhotographer' => $this->sendToPhotographer,
                'unsubscribeUrl' => $unsubscribeUrl,
            ],
        );
    }

    /**
     * Generate unsubscribe token for user.
     */
    private function generateUnsubscribeToken(): string
    {
        $user = $this->order->user;
        return hash('sha256', $user->id . $user->email . config('app.key'));
    }
}
