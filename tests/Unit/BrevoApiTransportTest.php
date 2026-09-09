<?php

namespace Tests\Unit;

use App\Mail\Transport\BrevoApiTransport;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class BrevoApiTransportTest extends TestCase
{
    public function test_it_sends_an_email_through_the_brevo_https_api(): void
    {
        Http::fake([
            'https://api.brevo.com/*' => Http::response(['messageId' => 'brevo-message-id'], 201),
        ]);

        $transport = new BrevoApiTransport(
            app(HttpClient::class),
            'test-api-key',
        );

        $sent = $transport->send(
            (new Email)
                ->from('sender@example.com')
                ->to('reader@example.com')
                ->subject('Verify your email')
                ->text('Open the verification link.')
                ->html('<p>Open the verification link.</p>')
        );

        $this->assertSame('brevo-message-id', $sent?->getMessageId());

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->hasHeader('api-key', 'test-api-key')
                && $request['sender']['email'] === 'sender@example.com'
                && $request['to'][0]['email'] === 'reader@example.com'
                && $request['subject'] === 'Verify your email'
                && $request['textContent'] === 'Open the verification link.'
                && $request['htmlContent'] === '<p>Open the verification link.</p>';
        });
    }

    public function test_it_reports_a_failed_brevo_api_response(): void
    {
        Http::fake([
            'https://api.brevo.com/*' => Http::response(['message' => 'unauthorized'], 401),
        ]);

        $transport = new BrevoApiTransport(
            app(HttpClient::class),
            'invalid-api-key',
        );

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('HTTP status 401');

        $transport->send(
            (new Email)
                ->from('sender@example.com')
                ->to('reader@example.com')
                ->subject('Verify your email')
                ->text('Open the verification link.')
        );
    }
}
