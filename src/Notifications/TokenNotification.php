<?php

namespace Kwidoo\Contacts\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TokenNotification extends Notification
{
    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your One-Time Token')
            ->line('Here is your one-time token:')
            ->line($this->token)
            ->line('Thank you for using our application!');
    }
}
