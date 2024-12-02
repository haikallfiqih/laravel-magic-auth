<?php

namespace LaravelLinkAuth\MagicAuth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Twilio\Rest\Client as TwilioClient;

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
        return $notifiable->magicLinkChannels ?? config('magic-auth.channels.default', ['mail']);
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
     * Send the WhatsApp message.
     *
     * @param mixed $notifiable
     * @return void
     */
    public function toWhatsapp($notifiable)
    {
        if (!class_exists(TwilioClient::class)) {
            throw new \RuntimeException('Twilio SDK is required for WhatsApp notifications. Please install twilio/sdk package.');
        }

        $message = config('magic-auth.whatsapp.message', 
            "Your login link for :app\n\nClick here to login: :url\n\nThis link will expire in :minutes minutes.");

        $message = strtr($message, [
            ':app' => config('app.name'),
            ':url' => $this->url,
            ':minutes' => $this->expiresInMinutes
        ]);

        $twilioClient = new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $twilioClient->messages->create(
            'whatsapp:' . $notifiable->routeNotificationFor('whatsapp'),
            [
                'from' => 'whatsapp:' . config('magic-auth.whatsapp.from'),
                'body' => $message
            ]
        );
    }

    /**
     * Send the SMS message.
     *
     * @param mixed $notifiable
     * @return void
     */
    public function toSms($notifiable)
    {
        if (!class_exists(TwilioClient::class)) {
            throw new \RuntimeException('Twilio SDK is required for SMS notifications. Please install twilio/sdk package.');
        }

        $message = config('magic-auth.sms.message', 
            "Your :app login link: :url (expires in :minutes minutes)");

        $message = strtr($message, [
            ':app' => config('app.name'),
            ':url' => $this->url,
            ':minutes' => $this->expiresInMinutes
        ]);

        $twilioClient = new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $twilioClient->messages->create(
            $notifiable->routeNotificationFor('sms'),
            [
                'from' => config('magic-auth.sms.from'),
                'body' => $message
            ]
        );
    }
}
