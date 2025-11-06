<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PreOrderCreatedNotification extends Notification
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
            ->subject('Pre-Order Confirmation #' . $this->order->id)
            ->greeting('Thank you for your pre-order!')
            ->line('Your pre-order for ' . $this->order->package->name . ' has been received.')
            ->line('Amount: $' . number_format($this->order->total, 2))
            ->line('After your photo session, you will be able to select your photos.')
            ->action('View Order', route('pre-orders.show', $this->order));
    }
}
