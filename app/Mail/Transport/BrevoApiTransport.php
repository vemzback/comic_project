<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\Factory as HttpClient;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class BrevoApiTransport extends AbstractTransport
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $apiKey,
        private readonly string $endpoint = 'https://api.brevo.com/v3/smtp/email',
        private readonly int $timeout = 15,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if ($this->apiKey === '') {
            throw new TransportException('The Brevo API key is not configured.');
        }

        $email = $message->getOriginalMessage();

        if (! $email instanceof Email) {
            throw new TransportException('Brevo API delivery requires an email message.');
        }

        $sender = $email->getFrom()[0] ?? null;

        if (! $sender instanceof Address) {
            throw new TransportException('The email sender is not configured.');
        }

        $payload = [
            'sender' => $this->formatAddress($sender),
            'to' => $this->formatAddresses($email->getTo()),
            'subject' => $email->getSubject() ?? '',
        ];

        $this->addAddresses($payload, 'cc', $email->getCc());
        $this->addAddresses($payload, 'bcc', $email->getBcc());

        if ($replyTo = $email->getReplyTo()[0] ?? null) {
            $payload['replyTo'] = $this->formatAddress($replyTo);
        }

        if (($html = $this->bodyToString($email->getHtmlBody())) !== null) {
            $payload['htmlContent'] = $html;
        }

        if (($text = $this->bodyToString($email->getTextBody())) !== null) {
            $payload['textContent'] = $text;
        }

        if (! isset($payload['htmlContent']) && ! isset($payload['textContent'])) {
            throw new TransportException('The email does not contain a readable body.');
        }

        try {
            $response = $this->http
                ->timeout(max(1, $this->timeout))
                ->acceptJson()
                ->withHeaders(['api-key' => $this->apiKey])
                ->post($this->endpoint, $payload);
        } catch (\Throwable $exception) {
            throw new TransportException('Brevo API delivery failed before receiving a response.', 0, $exception);
        }

        if ($response->failed()) {
            throw new TransportException(
                sprintf('Brevo API delivery failed with HTTP status %d.', $response->status())
            );
        }

        $messageId = $response->json('messageId');

        if (is_string($messageId) && $messageId !== '') {
            $message->setMessageId($messageId);
        }
    }

    public function __toString(): string
    {
        return 'brevo+api';
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array{email: string, name?: string}>
     */
    private function formatAddresses(array $addresses): array
    {
        return array_map(
            fn (Address $address): array => $this->formatAddress($address),
            $addresses,
        );
    }

    /**
     * @return array{email: string, name?: string}
     */
    private function formatAddress(Address $address): array
    {
        $formatted = ['email' => $address->getAddress()];

        if ($address->getName() !== '') {
            $formatted['name'] = $address->getName();
        }

        return $formatted;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, Address>  $addresses
     */
    private function addAddresses(array &$payload, string $key, array $addresses): void
    {
        if ($addresses !== []) {
            $payload[$key] = $this->formatAddresses($addresses);
        }
    }

    private function bodyToString(mixed $body): ?string
    {
        if (is_resource($body)) {
            $body = stream_get_contents($body);
        }

        return is_string($body) && $body !== '' ? $body : null;
    }
}
