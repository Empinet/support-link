# Support Link

Generate and validate signed context tokens as URL query parameters. This package allows applications to create authenticated URLs with embedded context — the receiving end validates the signature and extracts the payload.

## Installation

```bash
composer require empinet/support-link
```

## Usage

### Generating signed links

```php
use Empinet\SupportLink\SignedPayload;
use Empinet\SupportLink\SupportLinkGenerator;

$generator = SupportLinkGenerator::make(
    signingSecret: $sharedSecret,
    supportHubUrl: 'https://example.com/support',
);

$url = $generator->generate(new SignedPayload(
    name: 'John Doe',
    email: 'john@example.com',
    meta: [
        'plan' => 'pro',
        'tenant' => 'acme',
    ],
));
```

`name` and `email` are **required** constructor parameters. The `meta` array is optional and accepts arbitrary data.

### Validating tokens

```php
use Empinet\SupportLink\SignedContext;

$payload = SignedContext::validate($token, $sharedSecret);

if ($payload === null) {
    // Invalid, expired, or tampered
}

// $payload is ['name' => 'John', 'email' => 'john@...', 'plan' => 'pro', ...]
```

Tokens expire after 10 minutes by default. Pass a custom lifetime (in seconds) as the second argument to `generate()` or `createSignedToken()`.

## Token format

```
base64url(gzdeflate(JSON_payload)).base64url(HMAC-SHA256_signature)
```

- Payload is JSON-encoded and gzip-compressed before base64url encoding
- Signature is HMAC-SHA256 over the encoded payload
- Tokens include an `exp` expiration timestamp
- Validate handles both compressed and uncompressed payloads

## Security

- HMAC-signed to prevent tampering
- Configurable expiration window (default 10 minutes)
- Constant-time signature comparison (`hash_equals`)

## Requirements

- PHP 8.3 or later

## Testing

```bash
composer install
./vendor/bin/pest
```
