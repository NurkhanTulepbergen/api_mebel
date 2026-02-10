<?php
namespace App\Http\Traits;

trait TwoFactorTrait
{
    // Генерация секрета для TOTP
    public function generateSecret($length = 16): string {
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 (RFC 4648)
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $validChars[random_int(0, strlen($validChars) - 1)];
        }
        return $secret;
    }

    // Проверка TOTP-кода
    public function verifyCode(string $secret, string $code, int $window = 1): bool {
        $timestamp = floor(time() / 30); // каждые 30 сек

        for ($i = -$window; $i <= $window; $i++) {
            $calculated = $this->getTotpCode($secret, $timestamp + $i);
            if (hash_equals($calculated, $code)) {
                return true;
            }
        }

        return false;
    }

    // Генерация TOTP-кода
    public function getTotpCode(string $secret, int $timestamp): string {
        $secretKey = $this->base32Decode($secret);
        $binaryTimestamp = pack('N*', 0) . pack('N*', $timestamp);
        $hash = hash_hmac('sha1', $binaryTimestamp, $secretKey, true);

        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncatedHash = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad($truncatedHash % 1000000, 6, '0', STR_PAD_LEFT);
    }

    // Декодер Base32 (Google использует Base32)
    public function base32Decode(string $b32): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper($b32);
        $binaryString = '';

        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($b32); $i++) {
            $val = strpos($alphabet, $b32[$i]);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binaryString .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $binaryString;
    }
}
