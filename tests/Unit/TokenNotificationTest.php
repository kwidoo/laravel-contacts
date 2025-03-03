<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Kwidoo\Contacts\Notifications\TokenNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Kwidoo\Contacts\Tests\TestCase;

class TokenNotificationTest extends TestCase
{
    public function testTokenNotificationEmailMessage()
    {
        $token = '123456';
        $notification = new TokenNotification($token);
        $mailMessage = $notification->toMail((object) ['email' => 'test@example.com']);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals('Your One-Time Token', $mailMessage->subject);
        $this->assertStringContainsString('Here is your one-time token:', implode("\n", $mailMessage->introLines));
        $this->assertStringContainsString($token, implode("\n", $mailMessage->introLines));
        $this->assertStringContainsString('Thank you for using our application!', implode("\n", $mailMessage->outroLines));
    }
}
