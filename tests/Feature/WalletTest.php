<?php

declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Kurt\Modules\Loyalty\Exceptions\WalletNotConfiguredException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Wallet\AppleWalletProvider;
use Kurt\Modules\Loyalty\Wallet\GoogleWalletProvider;
use Kurt\Modules\Loyalty\Wallet\WalletManager;

beforeEach(function () {
    $this->program = Program::factory()->create(['stamps_required' => 7]);
    $this->card = Card::factory()->for($this->program)->create(['stamps_count' => 3]);
});

it('builds an apple storeCard payload', function () {
    $apple = new AppleWalletProvider([
        'enabled' => true,
        'pass_type_id' => 'pass.com.example.loyalty',
        'team_id' => 'ABCDE12345',
        'organization_name' => 'Example',
        'certificate' => '/does/not/exist.p12',
    ]);

    $pass = $apple->buildPass($this->card);

    expect($pass['serialNumber'])->toBe($this->card->token)
        ->and($pass['passTypeIdentifier'])->toBe('pass.com.example.loyalty')
        ->and($pass['storeCard']['primaryFields'][0]['value'])->toBe('3 / 7')
        ->and($pass['barcodes'][0]['message'])->toBe(strtoupper($this->card->token));
});

it('refuses to package a pkpass when unconfigured', function () {
    $apple = new AppleWalletProvider(['enabled' => false]);

    expect($apple->isConfigured())->toBeFalse();
    $apple->pkpass($this->card);
})->throws(WalletNotConfiguredException::class);

it('packages a signed pkpass zip when configured', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    if ($key === false) {
        $this->markTestSkipped('OpenSSL key generation unavailable.');
    }

    $csr = openssl_csr_new(['commonName' => 'loyalty-test'], $key);
    $cert = openssl_csr_sign($csr, null, $key, 365);
    openssl_pkcs12_export($cert, $p12, $key, 'secret');
    openssl_x509_export($cert, $wwdrPem);

    $p12Path = tempnam(sys_get_temp_dir(), 'lm_p12_');
    $wwdrPath = tempnam(sys_get_temp_dir(), 'lm_wwdr_');
    file_put_contents($p12Path, $p12);
    file_put_contents($wwdrPath, $wwdrPem);

    $apple = new AppleWalletProvider([
        'enabled' => true,
        'pass_type_id' => 'pass.com.example.loyalty',
        'team_id' => 'ABCDE12345',
        'certificate' => $p12Path,
        'certificate_password' => 'secret',
        'wwdr_certificate' => $wwdrPath,
    ]);

    $bytes = $apple->pkpass($this->card);

    $zipPath = tempnam(sys_get_temp_dir(), 'lm_out_');
    file_put_contents($zipPath, $bytes);
    $zip = new ZipArchive;
    expect($zip->open($zipPath))->toBeTrue();
    expect($zip->locateName('pass.json'))->not->toBeFalse();
    expect($zip->locateName('manifest.json'))->not->toBeFalse();
    expect($zip->locateName('signature'))->not->toBeFalse();
    $zip->close();

    @unlink($p12Path);
    @unlink($wwdrPath);
    @unlink($zipPath);
});

it('builds a google loyalty object', function () {
    $google = new GoogleWalletProvider([
        'enabled' => true,
        'issuer_id' => '3388000000000000000',
        'class_id' => '3388000000000000000.coffee',
        'service_account' => '/does/not/exist.json',
    ]);

    $object = $google->buildPass($this->card);

    expect($object['id'])->toBe('3388000000000000000.'.$this->card->token)
        ->and($object['classId'])->toBe('3388000000000000000.coffee')
        ->and($object['loyaltyPoints']['balance']['string'])->toBe('3 / 7')
        ->and($object['barcode']['value'])->toBe(strtoupper($this->card->token));
});

it('signs a google save url with the service account key', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    if ($key === false) {
        $this->markTestSkipped('OpenSSL key generation unavailable.');
    }
    openssl_pkey_export($key, $privatePem);
    $publicPem = openssl_pkey_get_details($key)['key'];

    $saPath = tempnam(sys_get_temp_dir(), 'lm_sa_');
    file_put_contents($saPath, (string) json_encode([
        'client_email' => 'svc@example.iam.gserviceaccount.com',
        'private_key' => $privatePem,
    ]));

    $google = new GoogleWalletProvider([
        'enabled' => true,
        'issuer_id' => '3388000000000000000',
        'class_id' => '3388000000000000000.coffee',
        'service_account' => $saPath,
    ]);

    $url = $google->saveUrl($this->card);
    expect($url)->toStartWith('https://pay.google.com/gp/v/save/');

    $jwt = substr($url, strlen('https://pay.google.com/gp/v/save/'));
    $decoded = JWT::decode($jwt, new Key($publicPem, 'RS256'));

    expect($decoded->iss)->toBe('svc@example.iam.gserviceaccount.com')
        ->and($decoded->typ)->toBe('savetowallet')
        ->and($decoded->payload->loyaltyObjects[0]->accountId)->toBe($this->card->token);

    @unlink($saPath);
});

it('reports availability through the manager', function () {
    config()->set('loyalty.wallet.apple.enabled', false);
    config()->set('loyalty.wallet.google.enabled', false);

    expect(app(WalletManager::class)->available())->toBe([]);
});
