<?php

declare(strict_types=1);

use Empinet\SupportLink\SignedContext;
use Empinet\SupportLink\SignedPayload;
use Empinet\SupportLink\SupportLinkGenerator;

test('generate returns a URL with signed token', function () {
    $generator = SupportLinkGenerator::make('shared-secret', 'https://support.empinet.com/bandplace');

    $link = $generator->generate(new SignedPayload(
        name: 'Test User',
        email: 'test@example.com',
    ));

    expect($link)->toContain('https://support.empinet.com/bandplace?token=');

    $queryString = parse_url($link, PHP_URL_QUERY);
    parse_str((string) $queryString, $params);
    expect($params)->toHaveKey('token');

    $payload = SignedContext::validate($params['token'], 'shared-secret');
    expect($payload)->not->toBeNull();
    expect($payload['email'])->toBe('test@example.com');
});

test('generate handles custom expiration', function () {
    $generator = SupportLinkGenerator::make('shared-secret', 'https://support.empinet.com/bandplace');
    $link = $generator->generate(new SignedPayload(
        name: 'Test User',
        email: 'test@example.com',
    ), 3600);

    $queryString = parse_url($link, PHP_URL_QUERY);
    parse_str((string) $queryString, $params);
    $payload = SignedContext::validate($params['token'], 'shared-secret');

    expect($payload)->not->toBeNull();
    expect($payload['exp'])->toBeGreaterThan(time() + 3500);
});

test('createSignedToken returns raw token', function () {
    $generator = SupportLinkGenerator::make('shared-secret', 'https://support.empinet.com/bandplace');
    $token = $generator->createSignedToken(new SignedPayload(
        name: 'Test User',
        email: 'test@example.com',
    ));

    expect($token)->toContain('.');
    $payload = SignedContext::validate($token, 'shared-secret');
    expect($payload)->not->toBeNull();
});

test('make static factory works', function () {
    $generator = SupportLinkGenerator::make('secret', 'https://example.com');

    expect($generator)->toBeInstanceOf(SupportLinkGenerator::class);

    $link = $generator->generate(new SignedPayload(
        name: 'Test',
        email: 'test@example.com',
    ));

    expect($link)->toStartWith('https://example.com?token=');
});

test('SignedPayload includes meta in token', function () {
    $generator = SupportLinkGenerator::make('shared-secret', 'https://support.empinet.com/bandplace');
    $link = $generator->generate(new SignedPayload(
        name: 'Test User',
        email: 'test@example.com',
        meta: [
            'plan' => 'pro',
            'workspace' => 'Acme Corp',
            'context' => ['account_id' => 42],
        ],
    ));

    $queryString = parse_url($link, PHP_URL_QUERY);
    parse_str((string) $queryString, $params);
    $payload = SignedContext::validate($params['token'], 'shared-secret');

    expect($payload['name'])->toBe('Test User');
    expect($payload['email'])->toBe('test@example.com');
    expect($payload['plan'])->toBe('pro');
    expect($payload['workspace'])->toBe('Acme Corp');
    expect($payload['context'])->toBe(['account_id' => 42]);
});
