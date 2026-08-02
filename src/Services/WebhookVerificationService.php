<?php

namespace MLQuarizm\PaymentGateway\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookVerificationService
{
    /**
     * Verify webhook signature for a specific gateway
     *
     * @param Request $request
     * @param string $gateway
     * @return bool
     */
    public function verify(Request $request, string $gateway): bool
    {
        return match (strtolower($gateway)) {
            'tamara' => $this->verifyTamara($request),
            'tabby' => $this->verifyTabby($request),
            default => $this->handleUnknownGateway($gateway),
        };
    }

    /**
     * Verify Tamara webhook token (JWT)
     *
     * @param Request $request
     * @return bool
     */
    private function verifyTamara(Request $request): bool
    {
        $notificationToken = config('tamara.notification_token');

        if (empty($notificationToken)) {
            Log::warning('Tamara webhook: Notification token not configured');
            return false;
        }

        $token = $request->query('tamaraToken');
        if (!$token) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            }
        }

        if (!$token) {
            Log::warning('Tamara webhook: No token provided for verification');
            return false;
        }

        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                Log::warning('Tamara webhook: Invalid JWT token format');
                return false;
            }

            [$header, $payload, $signature] = $parts;

            $headerData = json_decode(base64_decode(strtr($header, '-_', '+/')), true);
            $payloadData = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

            if (!$headerData || !$payloadData) {
                Log::warning('Tamara webhook: Failed to decode JWT token');
                return false;
            }

            if (($headerData['alg'] ?? null) !== 'HS256') {
                Log::warning('Tamara webhook: Unsupported JWT algorithm', ['alg' => $headerData['alg'] ?? null]);
                return false;
            }

            $expectedSignature = base64_encode(
                hash_hmac('sha256', $header . '.' . $payload, $notificationToken, true)
            );
            $expectedSignature = strtr(rtrim($expectedSignature, '='), '+/', '-_');

            if (!hash_equals($signature, $expectedSignature)) {
                Log::warning('Tamara webhook: JWT token signature verification failed');
                return false;
            }

            Log::info('Tamara webhook: JWT token verified successfully', ['payload' => $payloadData]);

            if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
                Log::warning('Tamara webhook: JWT token expired', ['exp' => $payloadData['exp']]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::warning('Tamara webhook: Error verifying JWT token', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Verify Tabby webhook
     *
     * Tabby does NOT use HMAC. When registering a webhook you provide a custom
     * header name + value. Tabby sends that exact header on every request.
     * We compare the received header value against the configured secret.
     *
     * Configure in .env:
     *   TABBY_WEBHOOK_VERIFY_SIGNATURE=true
     *   TABBY_WEBHOOK_HEADER=X-Tabby-Signature   (the header name you registered)
     *   TABBY_WEBHOOK_SECRET=your-random-secret   (the header value you registered)
     *
     * @param Request $request
     * @return bool
     */
    private function verifyTabby(Request $request): bool
    {
        if (!config('tabby.webhook_verify_signature', false)) {
            return true;
        }

        $expectedSecret = config('tabby.webhook_secret', '');
        $headerName     = config('tabby.webhook_header', 'X-Tabby-Signature');

        if (empty($expectedSecret)) {
            Log::warning('Tabby webhook: TABBY_WEBHOOK_SECRET is not configured');
            return false;
        }

        $receivedValue = $request->header($headerName);

        if (!$receivedValue) {
            Log::warning('Tabby webhook: Expected header not found', ['header' => $headerName]);
            return false;
        }

        if (!hash_equals($expectedSecret, $receivedValue)) {
            Log::warning('Tabby webhook: Header value mismatch', ['header' => $headerName]);
            return false;
        }

        Log::info('Tabby webhook: Verified successfully');
        return true;
    }

    /**
     * Handle unknown gateway
     *
     * @param string $gateway
     * @return bool
     */
    private function handleUnknownGateway(string $gateway): bool
    {
        Log::warning("Webhook verification: Unknown gateway '{$gateway}'");
        return false;
    }
}
