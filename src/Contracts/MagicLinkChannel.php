<?php

namespace LaravelLinkAuth\MagicAuth\Contracts;

interface MagicLinkChannel
{
    /**
     * Send the magic link through this channel.
     *
     * @param mixed $notifiable The user or contact to send the link to
     * @param string $url The magic link URL
     * @param int $expiresInMinutes Minutes until the link expires
     * @return void
     * @throws \Exception If sending fails
     */
    public function send($notifiable, string $url, int $expiresInMinutes);

    /**
     * Check if this channel can handle the given notifiable.
     *
     * @param mixed $notifiable
     * @return bool
     */
    public function canHandle($notifiable): bool;
}
