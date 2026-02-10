<?php

namespace App\Http\Traits\CosmoShop;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Domain;

trait CookieTrait
{
    public function createCookies()
    {
        $domains = Domain::getJv();
        foreach ($domains as $domain)
            $this->createCookieByDomain($domain);
    }

    private function createCookieByDomain(Domain $domain) {
        $response = $this->sendLoginRequest($domain);

        if ($response === false)
            return;

        [$headers, $body] = $this->splitResponse($response);
        $cookieArray = $this->parseCookiesFromHeaders($headers);
        $expiresAt = $this->getMinimumCookieExpiry($cookieArray);
        $finalCookieString = $this->buildCookieHeader($cookieArray);

        // $this->logCookieDebug($headers, $cookieArray, $finalCookieString, $domain, $expiresAt);
        $isSuccess = $this->verifyLoginSuccess($cookieArray, $body, $domain);
        if($isSuccess)
            $this->addCookiesToDomain($domain, $finalCookieString, $expiresAt);
    }

    private function sendLoginRequest(Domain $domain)
    {
        $loginUrl = "https://www.{$domain->link}/admin";

        $postData = http_build_query([
            'check_login' => '1',
            'u_name' => $domain->userCredentials->username,
            'pw' => $domain->userCredentials->password,
            'ls' => 'de'
        ]);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $loginUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Content-Type: application/x-www-form-urlencoded'
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            Log::channel('change-price')->error('CURL Error: ' . curl_error($ch));
        }

        curl_close($ch);
        return $response;
    }

    private function splitResponse(string $response): array
    {
        $headerSize = strpos($response, "\r\n\r\n") + 4;
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return [$headers, $body];
    }

    private function parseCookiesFromHeaders(string $headers): array
    {
        preg_match_all('/^Set-Cookie:\s*(.+)$/mi', $headers, $matches);

        $cookieArray = [];

        if (!empty($matches[1])) {
            foreach ($matches[1] as $cookieLine) {
                $parts = explode(';', $cookieLine);
                $main = array_shift($parts);

                [$name, $value] = explode('=', $main, 2);
                $expiresAt = null;

                foreach ($parts as $part) {
                    $part = trim($part);
                    if (stripos($part, 'expires=') === 0) {
                        $expiresRaw = trim(substr($part, 8));
                        try {
                            $expiresAt = Carbon::parse($expiresRaw)->timestamp;
                        } catch (\Exception $e) {
                            Log::channel('change-price')->warning("⚠️ Invalid expires format: " . $expiresRaw);
                        }
                        break;
                    }
                }

                $cookieArray[$name] = [
                    'value' => $value,
                    'expires_at' => $expiresAt,
                ];
            }
        }

        return $cookieArray;
    }

    private function getMinimumCookieExpiry(array $cookies): ?int
    {
        $minExpiry = null;

        foreach ($cookies as $cookie) {
            if (!empty($cookie['expires_at'])) {
                $expiry = (int) $cookie['expires_at'];

                if (is_null($minExpiry) || $expiry < $minExpiry) {
                    $minExpiry = $expiry;
                }
            }
        }

        return $minExpiry;
    }



    private function buildCookieHeader(array $cookieArray): string
    {
        $parts = [];

        foreach ($cookieArray as $name => $cookie) {
            $parts[] = $name . '=' . $cookie['value'];
        }

        return implode('; ', $parts);
    }

    private function logCookieDebug(string $headers, array $cookieArray, string $cookieHeader, Domain $domain, $expiresAt): void
    {
        Log::channel('change-price')->info("=== DOMAIN === " . $domain->name);
        Log::channel('change-price')->info("=== COOKIES FROM HEADERS ===\n" . $headers);
        Log::channel('change-price')->info("=== PARSED COOKIE ARRAY ===\n" . print_r($cookieArray, true));
        Log::channel('change-price')->info("🍪 Final cookie string to use in requests:\n" . $cookieHeader);
        Log::channel('change-price')->info("Expires At: \n" . $readable = date('Y-m-d H:i:s', $expiresAt));
    }

    private function verifyLoginSuccess(array $cookieArray, string $body, Domain $domain): bool
    {
        $requiredCookies = ["SHOP_SESSION_www.{$domain->link}", 'SHOP_ADMIN_ID'];
        $hasAll = true;

        foreach ($requiredCookies as $cookieName) {
            if (!isset($cookieArray[$cookieName])) {
                $hasAll = false;
                Log::channel('change-price')->warning("❌ Missing required cookie: {$cookieName}");
            }
        }

        $loginFormVisible = str_contains($body, 'name="u_name"') && str_contains($body, 'name="pw"');
        $loginError = str_contains($body, 'class="login-error"') || str_contains($body, 'incorrect') || str_contains($body, 'Please active cookies!');

        if (!$hasAll && $loginFormVisible && $loginError) {
            Log::channel('change-price')->error("❗ Не удалось войти. Либо куки не получены, либо форма логина всё ещё отображается.");
            Log::channel('change-price')->debug("ℹ️ Тело ответа:\n" . $body);
            return false;
        }
        return true;
    }

    private function addCookiesToDomain(Domain $domain, $content, $expiresAt) {
        $updateValues = [
            'content'    => $content,
            'expires_at' => Carbon::createFromTimestamp($expiresAt)->toDateTimeString()
        ];
        if($domain->cookies)
            $domain->cookies()->update($updateValues);
        else
            $domain->cookies()->create($updateValues);
    }

}
