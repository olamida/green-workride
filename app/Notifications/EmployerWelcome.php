<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when an organisation auto-creates a staff account from a roster upload
 * (guide §7 Form 2). Carries the temporary password; the employee sets their
 * own password on the Profile & safety page.
 */
class EmployerWelcome extends Notification
{
    public function __construct(public string $temporaryPassword) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array{title: string, body: string}
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Your employer enrolled you on WorkRide',
            'body' => 'Your organisation created your WorkRide account. Your temporary password is '.$this->temporaryPassword.'. Sign in and change it in Profile & safety.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your WorkRide account is ready')
            ->line('Your organisation enrolled you on WorkRide so your commute can be covered.')
            ->line('Email: '.$notifiable->email)
            ->line('Temporary password: '.$this->temporaryPassword)
            ->action('Sign in to WorkRide', url('/login'))
            ->line('Change your password in Profile & safety after your first sign-in.');
    }
}
