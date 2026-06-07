<?php

declare(strict_types=1);

use Empinet\SupportLink\SignedContext;

test('generate returns a token with payload and signature parts', function () {
    $token = SignedContext::generate(['email' => 'test@example.com'], 'secret');

    expect($token)->toContain('.');
});

test('validate returns decoded payload for valid token', function () {
    $token = SignedContext::generate(['email' => 'test@example.com'], 'secret');

    $payload = SignedContext::validate($token, 'secret');

    expect($payload)->not->toBeNull();
    expect($payload['email'])->toBe('test@example.com');
});

test('validate returns null for wrong secret', function () {
    $token = SignedContext::generate(['email' => 'test@example.com'], 'secret');

    $payload = SignedContext::validate($token, 'wrong-secret');

    expect($payload)->toBeNull();
});

test('validate returns null for tampered payload', function () {
    $token = SignedContext::generate(['email' => 'test@example.com'], 'secret');

    [$encodedPayload, $signature] = explode('.', $token);
    $tamperedPayload = base64_encode((string) json_encode(['email' => 'hacked@evil.com', 'exp' => time() + 600]));
    $tamperedPayload = rtrim(strtr($tamperedPayload, '+/', '-_'), '=');
    $tamperedToken = $tamperedPayload.'.'.$signature;

    $payload = SignedContext::validate($tamperedToken, 'secret');

    expect($payload)->toBeNull();
});

test('validate returns null for malformed token', function () {
    expect(SignedContext::validate('not-a-valid-token', 'secret'))->toBeNull();
    expect(SignedContext::validate('a.b.c', 'secret'))->toBeNull();
});

test('validate returns null for expired token', function () {
    $token = SignedContext::generate(['email' => 'test@example.com'], 'secret', -1);

    $payload = SignedContext::validate($token, 'secret');

    expect($payload)->toBeNull();
});

test('base64urlEncode produces URL-safe encoding', function () {
    $encoded = SignedContext::base64urlEncode('test+data/with=padding');

    expect($encoded)->not->toContain('+');
    expect($encoded)->not->toContain('/');
    expect($encoded)->not->toContain('=');
});

test('base64url roundtrip works', function () {
    $original = 'some binary data with +/ and ==';
    $encoded = SignedContext::base64urlEncode($original);
    $decoded = SignedContext::base64urlDecode($encoded);

    expect($decoded)->toBe($original);
});

test('generated token includes exp key', function () {
    $token = SignedContext::generate(['email' => 'test@example.com'], 'secret', 300);

    $payload = SignedContext::validate($token, 'secret');

    expect($payload)->toHaveKey('exp');
    expect($payload['exp'])->toBeGreaterThan(time() + 290);
    expect($payload['exp'])->toBeLessThanOrEqual(time() + 300);
});

test('generate preserves original payload keys', function () {
    $token = SignedContext::generate([
        'email' => 'user@example.com',
        'name' => 'John Doe',
        'plan' => 'pro',
    ], 'secret');

    $payload = SignedContext::validate($token, 'secret');

    expect($payload['email'])->toBe('user@example.com');
    expect($payload['name'])->toBe('John Doe');
    expect($payload['plan'])->toBe('pro');
});
