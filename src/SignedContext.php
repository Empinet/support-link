<?php

declare(strict_types=1);

namespace Empinet\SupportLink;

class SignedContext
{
    public static function generate(
        array $payload,
        string $secret,
        ?int $expiresAfterSeconds = 600,
    ): string {
        $payload['exp'] = time() + $expiresAfterSeconds;

        $json = (string) json_encode($payload);
        $compressed = gzdeflate($json, 9);
        $encodedPayload = self::base64urlEncode($compressed);
        $signature = hash_hmac('sha256', $encodedPayload, $secret, binary: true);

        return $encodedPayload.'.'.self::base64urlEncode($signature);
    }

    public static function validate(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $encodedSignature] = $parts;

        $expectedSignature = hash_hmac('sha256', $encodedPayload, $secret, binary: true);

        if (! hash_equals(self::base64urlEncode($expectedSignature), $encodedSignature)) {
            return null;
        }

        $payload = self::decodePayload($encodedPayload);

        if (! is_array($payload) || ! isset($payload['exp'])) {
            return null;
        }

        if ($payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    public static function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private static function decodePayload(string $encodedPayload): ?array
    {
        $binary = self::base64urlDecode($encodedPayload);

        $decompressed = @gzinflate($binary);

        if ($decompressed !== false) {
            return json_decode($decompressed, true);
        }

        return json_decode($binary, true);
    }
}
