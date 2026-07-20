<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Models\WalletRegistration;
use Kurt\Modules\Loyalty\Wallet\AppleWalletProvider;
use Kurt\Modules\Loyalty\Wallet\GoogleWalletProvider;

beforeEach(function () {
    $this->card = Card::factory()->for(Program::factory()->create())->create(['stamps_count' => 2]);
});

it('patches the google loyalty object with an OAuth token on push', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    if ($key === false) {
        $this->markTestSkipped('OpenSSL key generation unavailable.');
    }
    openssl_pkey_export($key, $privatePem);

    $saPath = tempnam(sys_get_temp_dir(), 'lm_sa_');
    file_put_contents($saPath, (string) json_encode([
        'client_email' => 'svc@example.iam.gserviceaccount.com',
        'private_key' => $privatePem,
    ]));

    Http::fake([
        'oauth2.googleapis.com/*' => Http::response(['access_token' => 'tok-123']),
        'walletobjects.googleapis.com/*' => Http::response(['id' => 'x']),
    ]);

    $google = new GoogleWalletProvider([
        'enabled' => true,
        'issuer_id' => '3388000000000000000',
        'class_id' => '3388000000000000000.coffee',
        'service_account' => $saPath,
        'token_endpoint' => 'https://oauth2.googleapis.com/token',
        'api_base' => 'https://walletobjects.googleapis.com/walletobjects/v1',
    ]);

    $google->pushUpdate($this->card);

    Http::assertSent(fn ($req) => str_contains($req->url(), '/loyaltyObject/')
        && $req->method() === 'PATCH'
        && $req->hasHeader('Authorization', 'Bearer tok-123'));

    @unlink($saPath);
});

it('sends an APNs push per registered device on apple push', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    if ($key === false) {
        $this->markTestSkipped('OpenSSL key generation unavailable.');
    }
    $csr = openssl_csr_new(['commonName' => 'loyalty-test'], $key);
    $cert = openssl_csr_sign($csr, null, $key, 365);
    openssl_pkcs12_export($cert, $p12, $key, 'secret');
    $p12Path = tempnam(sys_get_temp_dir(), 'lm_p12_');
    file_put_contents($p12Path, $p12);

    WalletRegistration::query()->create([
        'device_library_id' => 'dev-1',
        'pass_serial' => $this->card->token,
        'push_token' => 'ptoken-xyz',
    ]);

    Http::fake(['*' => Http::response('', 200)]);

    $apple = new AppleWalletProvider([
        'enabled' => true,
        'pass_type_id' => 'pass.com.example.loyalty',
        'team_id' => 'ABCDE12345',
        'certificate' => $p12Path,
        'certificate_password' => 'secret',
        'apns_host' => 'https://api.push.apple.com',
    ]);

    $apple->pushUpdate($this->card);

    Http::assertSent(fn ($req) => str_contains($req->url(), '/3/device/ptoken-xyz'));

    @unlink($p12Path);
});
