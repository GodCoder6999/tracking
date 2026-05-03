<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(public Order $order, public string $message) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order {$this->order->order_number} — {$this->message}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Update on your order {$this->order->order_number}:")
            ->line($this->message)
            ->line('Payment: '.strtoupper($this->order->payment_status))
            ->line('Dispatch: '.strtoupper($this->order->dispatch_status))
            ->action('View Order', url('/client/orders/'.$this->order->id))
            ->line('Thank you.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'message'      => $this->message,
        ];
    }
}
