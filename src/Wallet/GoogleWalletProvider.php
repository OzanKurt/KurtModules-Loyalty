<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Wallet;

use Firebase\JWT\JWT;
use Kurt\Modules\Loyalty\Contracts\WalletProvider;
use Kurt\Modules\Loyalty\Exceptions\WalletNotConfiguredException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Support\CardState;

/**
 * Builds Google Wallet loyalty objects and the signed "Add to Google Wallet"
 * JWT save link. Requires an issuer id, class id, and a service-account key.
 */
final class GoogleWalletProvider implements WalletProvider
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    public function isConfigured(): bool
    {
        return (bool) ($this->config['enabled'] ?? false)
            && ! empty($this->config['issuer_id'])
            && ! empty($this->config['class_id'])
            && ! empty($this->config['service_account']);
    }

    public function serialFor(Card $card): string
    {
        return ((string) ($this->config['issuer_id'] ?? '')).'.'.$card->token;
    }

    /**
     * The Google Wallet loyalty object for a card.
     *
     * @return array<string, mixed>
     */
    public function buildPass(Card $card): array
    {
        $state = CardState::for($card);

        return [
            'id' => $this->serialFor($card),
            'classId' => (string) ($this->config['class_id'] ?? ''),
            'state' => 'ACTIVE',
            'accountName' => (string) ($state['program']['name']),
            'accountId' => $card->token,
            'loyaltyPoints' => [
                'label' => 'Stamps',
                'balance' => ['string' => $state['stamps_count'].' / '.$state['program']['stamps_required']],
            ],
            'barcode' => [
                'type' => 'QR_CODE',
                'value' => (string) $state['code'],
                'alternateText' => (string) $state['code'],
            ],
        ];
    }

    /**
     * Build the signed "Add to Google Wallet" save URL for a card.
     */
    public function saveUrl(Card $card): string
    {
        $account = $this->serviceAccount();

        $claims = [
            'iss' => $account['client_email'],
            'aud' => 'google',
            'typ' => 'savetowallet',
            'iat' => 0,
            'payload' => [
                'loyaltyObjects' => [$this->buildPass($card)],
            ],
        ];

        $jwt = JWT::encode($claims, (string) $account['private_key'], 'RS256');

        return 'https://pay.google.com/gp/v/save/'.$jwt;
    }

    public function pushUpdate(Card $card): void
    {
        // Live update patches the loyalty object via the Google Wallet API.
        // Opt-in; a no-op until the app supplies API credentials + a queue.
    }

    /**
     * @return array{client_email: string, private_key: string}
     */
    private function serviceAccount(): array
    {
        $path = $this->config['service_account'] ?? null;

        if (! is_string($path) || ! is_file($path)) {
            throw new WalletNotConfiguredException('Google service account file not found.');
        }

        /** @var array{client_email?: string, private_key?: string} $data */
        $data = (array) json_decode((string) file_get_contents($path), true);

        if (empty($data['client_email']) || empty($data['private_key'])) {
            throw new WalletNotConfiguredException('Google service account is missing client_email or private_key.');
        }

        return ['client_email' => (string) $data['client_email'], 'private_key' => (string) $data['private_key']];
    }
}
