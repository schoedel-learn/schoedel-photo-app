<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order,
        public Transaction $transaction
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Receipt - Order #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $receiptUrl = $this->transaction->metadata['receipt_url'] ?? null;
        $orderUrl = route('orders.show', $this->order);
        $unsubscribeUrl = route('unsubscribe', ['token' => $this->generateUnsubscribeToken()]);

        // Get download links for this order
        $downloadService = app(\App\Services\DownloadService::class);
        $downloads = \App\Models\Download::where('order_id', $this->order->id)
            ->where('user_id', $this->order->user_id)
            ->with('photo')
            ->get();

        $downloadLinks = $downloads->map(function ($download) use ($downloadService) {
            return [
                'photo' => $download->photo,
                'url' => $downloadService->getDownloadUrl($download),
            ];
        });

        return new Content(
            view: 'emails.payment-receipt',
            with: [
                'order' => $this->order,
                'transaction' => $this->transaction,
                'receiptUrl' => $receiptUrl,
                'orderUrl' => $orderUrl,
                'unsubscribeUrl' => $unsubscribeUrl,
                'downloadLinks' => $downloadLinks,
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
