<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PreOrderReadyToFinalizeNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ready to Select Your Photos - Pre-Order #' . $this->order->id)
            ->greeting('Your photos are ready!')
            ->line('Your photo gallery is now available. Please select ' . $this->order->package->photo_count . ' photos to complete your pre-order.')
            ->line('Additional photos can be added for $5.00 each.')
            ->action('Select Photos', route('pre-orders.finalize', $this->order));
    }
}
