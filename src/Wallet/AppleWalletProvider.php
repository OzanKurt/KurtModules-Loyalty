<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Wallet;

use Kurt\Modules\Loyalty\Contracts\WalletProvider;
use Kurt\Modules\Loyalty\Exceptions\WalletNotConfiguredException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Support\CardState;
use ZipArchive;

/**
 * Builds Apple Wallet `.pkpass` bundles (storeCard style).
 *
 * The pass.json payload is always buildable (and unit-tested); packaging into a
 * signed `.pkpass` requires an Apple Pass Type certificate + WWDR certificate,
 * so `pkpass()` throws when those are absent.
 */
final class AppleWalletProvider implements WalletProvider
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    public function isConfigured(): bool
    {
        return (bool) ($this->config['enabled'] ?? false)
            && ! empty($this->config['pass_type_id'])
            && ! empty($this->config['team_id'])
            && ! empty($this->config['certificate']);
    }

    public function serialFor(Card $card): string
    {
        return $card->token;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPass(Card $card): array
    {
        $state = CardState::for($card);

        return [
            'formatVersion' => 1,
            'passTypeIdentifier' => (string) ($this->config['pass_type_id'] ?? ''),
            'teamIdentifier' => (string) ($this->config['team_id'] ?? ''),
            'organizationName' => (string) ($this->config['organization_name'] ?? 'Loyalty'),
            'serialNumber' => $this->serialFor($card),
            'description' => (string) $state['program']['name'],
            'backgroundColor' => 'rgb(23, 18, 14)',
            'foregroundColor' => 'rgb(239, 229, 214)',
            'labelColor' => 'rgb(201, 166, 107)',
            'logoText' => (string) $state['program']['name'],
            'barcodes' => [[
                'format' => 'PKBarcodeFormatQR',
                'message' => (string) $state['code'],
                'messageEncoding' => 'iso-8859-1',
                'altText' => (string) $state['code'],
            ]],
            'storeCard' => [
                'primaryFields' => [[
                    'key' => 'stamps',
                    'label' => 'STAMPS',
                    'value' => $state['stamps_count'].' / '.$state['program']['stamps_required'],
                ]],
                'secondaryFields' => [[
                    'key' => 'reward',
                    'label' => 'REWARD',
                    'value' => (string) $state['program']['reward'],
                ]],
                'auxiliaryFields' => [[
                    'key' => 'available',
                    'label' => 'REWARDS READY',
                    'value' => (string) $state['rewards_available'],
                ]],
            ],
        ];
    }

    /**
     * Build a signed `.pkpass` archive and return its raw bytes.
     */
    public function pkpass(Card $card): string
    {
        if (! $this->isConfigured()) {
            throw new WalletNotConfiguredException('Apple Wallet is not configured.');
        }

        $pass = json_encode($this->buildPass($card), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($pass === false) {
            throw new WalletNotConfiguredException('Unable to encode pass payload.');
        }

        $files = ['pass.json' => $pass];

        $iconPath = $this->config['icon'] ?? null;
        if (is_string($iconPath) && is_file($iconPath)) {
            $icon = (string) file_get_contents($iconPath);
            $files['icon.png'] = $icon;
            $files['icon@2x.png'] = $icon;
        }

        $manifest = [];
        foreach ($files as $name => $contents) {
            $manifest[$name] = sha1($contents);
        }
        $manifestJson = (string) json_encode($manifest, JSON_UNESCAPED_SLASHES);

        $files['manifest.json'] = $manifestJson;
        $files['signature'] = $this->sign($manifestJson);

        return $this->zip($files);
    }

    public function pushUpdate(Card $card): void
    {
        // Live push over the Apple Wallet web service (APNs) is opt-in and
        // requires a configured web service URL + push credentials + queue.
        // Intentionally a no-op until that infrastructure is wired by the app.
    }

    private function sign(string $manifest): string
    {
        $certPath = (string) $this->config['certificate'];
        $wwdrPath = (string) ($this->config['wwdr_certificate'] ?? '');
        $password = (string) ($this->config['certificate_password'] ?? '');

        if (! is_file($certPath) || ! is_file($wwdrPath)) {
            throw new WalletNotConfiguredException('Apple certificate or WWDR certificate not found.');
        }

        $pkcs12 = (string) file_get_contents($certPath);
        $certs = [];
        if (! openssl_pkcs12_read($pkcs12, $certs, $password)) {
            throw new WalletNotConfiguredException('Unable to read the Apple certificate.');
        }

        $manifestFile = tempnam(sys_get_temp_dir(), 'lm_');
        $signatureFile = tempnam(sys_get_temp_dir(), 'lm_');
        file_put_contents((string) $manifestFile, $manifest);

        openssl_pkcs7_sign(
            (string) $manifestFile,
            (string) $signatureFile,
            $certs['cert'],
            [$certs['pkey'], $password],
            [],
            PKCS7_BINARY | PKCS7_DETACHED,
            $wwdrPath,
        );

        $signed = (string) file_get_contents((string) $signatureFile);
        @unlink((string) $manifestFile);
        @unlink((string) $signatureFile);

        // Strip the S/MIME headers, keep the DER body.
        $parts = explode("\n\n", str_replace("\r", '', $signed), 2);

        return isset($parts[1]) ? (string) base64_decode(trim($parts[1]), true) : $signed;
    }

    /**
     * @param  array<string, string>  $files
     */
    private function zip(array $files): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'lm_pkpass_');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        $bytes = (string) file_get_contents($path);
        @unlink($path);

        return $bytes;
    }
}
