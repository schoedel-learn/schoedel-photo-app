<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PreOrderFinalizedNotification extends Notification
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
        $message = (new MailMessage)
            ->subject('Pre-Order Finalized #' . $this->order->id)
            ->greeting('Your pre-order has been finalized!')
            ->line('You selected ' . $this->order->selected_photo_count . ' photos.');

        if ($this->order->upsell_amount > 0) {
            $message->line('Additional photos: $' . number_format($this->order->upsell_amount, 2));
        }

        $message->line('Total: $' . number_format($this->order->total, 2))
            ->line('Your photos will be available for download shortly.');

        return $message;
    }
}
