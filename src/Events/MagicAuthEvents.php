<?php

namespace LaravelLinkAuth\MagicAuth\Events;

class MagicAuthEvents
{
    /**
     * Event fired before generating a magic link
     */
    const GENERATING = 'magic-auth.link.generating';

    /**
     * Event fired after a magic link has been sent
     */
    const SENT = 'magic-auth.link.sent';

    /**
     * Event fired when magic link sending fails
     */
    const FAILED = 'magic-auth.link.failed';

    /**
     * Event fired when verification process starts
     */
    const VERIFICATION_STARTED = 'magic-auth.verification.started';

    /**
     * Event fired after successful verification and login
     */
    const VERIFICATION_COMPLETED = 'magic-auth.verification.completed';

    /**
     * Event fired when verification fails
     */
    const VERIFICATION_FAILED = 'magic-auth.verification.failed';

    /**
     * Event fired when verification encounters an error
     */
    const VERIFICATION_ERROR = 'magic-auth.verification.error';
}
