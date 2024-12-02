<?php

namespace LaravelLinkAuth\MagicAuth\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use LaravelLinkAuth\MagicAuth\MagicAuth;
use LaravelLinkAuth\MagicAuth\Tests\TestCase;

class MagicAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** @test */
    public function it_can_send_magic_link()
    {
        $email = 'test@example.com';
        $magicAuth = app(MagicAuth::class);

        $result = $magicAuth->sendMagicLink($email, 'web');

        $this->assertTrue($result);
        $this->assertDatabaseHas('magic_links', [
            'email' => $email,
            'guard' => 'web',
            'used' => false,
        ]);
    }

    /** @test */
    public function it_can_send_magic_link_for_admin()
    {
        $email = 'admin@example.com';
        $magicAuth = app(MagicAuth::class);

        $result = $magicAuth->sendMagicLink($email, 'admin');

        $this->assertTrue($result);
        $this->assertDatabaseHas('magic_links', [
            'email' => $email,
            'guard' => 'admin',
            'used' => false,
        ]);
    }

    /** @test */
    public function it_validates_guard_type()
    {
        $this->expectException(\InvalidArgumentException::class);

        $magicAuth = app(MagicAuth::class);
        $magicAuth->sendMagicLink('test@example.com', 'invalid-guard');
    }

    /** @test */
    public function it_marks_token_as_used_after_verification()
    {
        $email = 'test@example.com';
        $magicAuth = app(MagicAuth::class);

        // Send magic link
        $magicAuth->sendMagicLink($email, 'web');

        // Get the token
        $token = \DB::table('magic_links')
            ->where('email', $email)
            ->first()
            ->token;

        // Verify the token
        $result = $magicAuth->verifyAndLogin($token, 'web');

        $this->assertNotFalse($result);
        $this->assertDatabaseHas('magic_links', [
            'token' => $token,
            'used' => true,
        ]);
    }

    /** @test */
    public function it_prevents_reuse_of_tokens()
    {
        $email = 'test@example.com';
        $magicAuth = app(MagicAuth::class);

        // Send magic link
        $magicAuth->sendMagicLink($email, 'web');

        // Get the token
        $token = \DB::table('magic_links')
            ->where('email', $email)
            ->first()
            ->token;

        // First verification should succeed
        $result1 = $magicAuth->verifyAndLogin($token, 'web');
        $this->assertNotFalse($result1);

        // Second verification should fail
        $result2 = $magicAuth->verifyAndLogin($token, 'web');
        $this->assertFalse($result2);
    }
}
