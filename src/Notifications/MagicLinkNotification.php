<?php

namespace LaravelLinkAuth\MagicAuth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\WhatsApp\WhatsAppMessage;

class MagicLinkNotification extends Notification
{
    use Queueable;

    /**
     * The magic link URL.
     *
     * @var string
     */
    protected $url;

    /**
     * The expiration time in minutes.
     *
     * @var int
     */
    protected $expiresInMinutes;

    /**
     * Create a new notification instance.
     *
     * @param string $url
     * @param int $expiresInMinutes
     * @return void
     */
    public function __construct(string $url, int $expiresInMinutes)
    {
        $this->url = $url;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return $notifiable->magicLinkChannels ?? config('magic-auth.channels', ['mail']);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(config('magic-auth.mail.subject', 'Your Magic Login Link'))
            ->markdown('magic-auth::emails.magic-link', [
                'url' => $this->url,
                'expiresInMinutes' => $this->expiresInMinutes
            ]);
    }

    /**
     * Get the WhatsApp representation of the notification.
     *
     * @param mixed $notifiable
     * @return \NotificationChannels\WhatsApp\WhatsAppMessage
     */
    public function toWhatsApp($notifiable)
    {
        $message = config('magic-auth.whatsapp.message', 
            "Your login link for :app\n\nClick here to login: :url\n\nThis link will expire in :minutes minutes.");

        $message = strtr($message, [
            ':app' => config('app.name'),
            ':url' => $this->url,
            ':minutes' => $this->expiresInMinutes
        ]);

        return WhatsAppMessage::create()
            ->content($message);
    }

    /**
     * Get the SMS representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toSms($notifiable)
    {
        $message = config('magic-auth.sms.message', 
            "Your :app login link: :url (expires in :minutes minutes)");

        return strtr($message, [
            ':app' => config('app.name'),
            ':url' => $this->url,
            ':minutes' => $this->expiresInMinutes
        ]);
    }
}
