<?php

namespace LaravelLinkAuth\MagicAuth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelLinkAuth\MagicAuth\Tests\TestCase;
use LaravelLinkAuth\MagicAuth\Facades\MagicAuth;
use LaravelLinkAuth\MagicAuth\Facades\Events;
use LaravelLinkAuth\MagicAuth\Events\MagicAuthEvents;
use Illuminate\Support\Facades\Notification;

class MagicAuthTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /** @test */
    public function it_can_send_magic_link_via_email()
    {
        $email = 'test@example.com';
        
        $result = MagicAuth::sendMagicLink($email);
        
        $this->assertTrue($result);
        $this->assertDatabaseHas('magic_links', [
            'identifier' => $email,
        ]);
    }

    /** @test */
    public function it_fires_events_when_sending_magic_link()
    {
        $events = [];
        Events::listen(MagicAuthEvents::GENERATING, function () use (&$events) {
            $events[] = 'generating';
        });
        Events::listen(MagicAuthEvents::SENT, function () use (&$events) {
            $events[] = 'sent';
        });

        MagicAuth::sendMagicLink('test@example.com');

        $this->assertEquals(['generating', 'sent'], $events);
    }

    /** @test */
    public function it_validates_magic_link_expiration()
    {
        $email = 'test@example.com';
        
        // Create an expired link
        $link = MagicAuth::sendMagicLink($email);
        $this->travel(config('magic-auth.expires') + 1)->minutes();
        
        $result = MagicAuth::verifyAndLogin($link);
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_prevents_reuse_of_magic_links()
    {
        $email = 'test@example.com';
        $link = MagicAuth::sendMagicLink($email);
        
        // First use should succeed
        $result1 = MagicAuth::verifyAndLogin($link);
        $this->assertTrue($result1);
        
        // Second use should fail
        $result2 = MagicAuth::verifyAndLogin($link);
        $this->assertFalse($result2);
    }

    /** @test */
    public function it_respects_rate_limiting()
    {
        $email = 'test@example.com';
        $maxAttempts = config('magic-auth.throttle.max_attempts');
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            MagicAuth::sendMagicLink($email);
        }
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        MagicAuth::sendMagicLink($email);
    }
}
