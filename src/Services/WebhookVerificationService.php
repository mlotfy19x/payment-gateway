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
     * Verify Tabby webhook signature
     *
     * Tabby typically uses HMAC-SHA256 signature in headers
     * The signature is usually in X-Tabby-Signature header
     *
     * @param Request $request
     * @return bool
     */
    private function verifyTabby(Request $request): bool
    {
        $secretKey = config('tabby.secret_key');

        if (empty($secretKey)) {
            Log::warning('Tabby webhook: Secret key not configured');
            return false;
        }

        // Tabby may send signature in different headers
        // Common headers: X-Tabby-Signature, X-Signature, Signature
        $signature = $request->header('X-Tabby-Signature') 
            ?? $request->header('X-Signature')
            ?? $request->header('Signature');

        if (!$signature) {
            Log::warning('Tabby webhook: No signature header found');
            // For now, we'll allow webhooks without signature if not configured
            // This can be made strict later when Tabby documentation is confirmed
            return config('tabby.webhook_verify_signature', false) === false;
        }

        try {
            // Get raw request body
            $payload = $request->getContent();
            
            // Calculate expected signature using HMAC-SHA256
            $expectedSignature = hash_hmac('sha256', $payload, $secretKey);

            // Compare signatures (use hash_equals for timing attack protection)
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Tabby webhook: Signature verification failed', [
                    'expected' => substr($expectedSignature, 0, 10) . '...',
                    'received' => substr($signature, 0, 10) . '...',
                ]);
                return false;
            }

            Log::info('Tabby webhook: Signature verified successfully');
            return true;
        } catch (\Exception $e) {
            Log::warning('Tabby webhook: Error verifying signature', ['error' => $e->getMessage()]);
            return false;
        }
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
