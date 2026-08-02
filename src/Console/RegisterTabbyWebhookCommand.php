<?php

namespace MLQuarizm\PaymentGateway\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RegisterTabbyWebhookCommand extends Command
{
    protected $signature = 'tabby:webhook
                            {action : Action to perform: register, list, delete}
                            {--url= : Webhook URL (defaults to APP_URL/webhooks/payment/tabby)}
                            {--id= : Webhook ID (required for delete)}';

    protected $description = 'Manage Tabby webhook registrations via Tabby API';

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'register' => $this->register(),
            'list'     => $this->listWebhooks(),
            'delete'   => $this->delete(),
            default    => $this->invalidAction($action),
        };
    }

    private function register(): int
    {
        $url    = $this->option('url') ?? url('/webhooks/payment/tabby');
        $header = config('tabby.webhook_header', 'X-Tabby-Signature');
        $secret = config('tabby.webhook_secret', '');

        if (empty($secret)) {
            $this->error('TABBY_WEBHOOK_SECRET is not set in your .env file.');
            $this->line('Add a random secret string:  TABBY_WEBHOOK_SECRET=your-random-secret-here');
            return self::FAILURE;
        }

        $this->info("Registering Tabby webhook...");
        $this->line("  URL:    {$url}");
        $this->line("  Header: {$header}: {$secret}");

        $response = $this->apiRequest('POST', '/api/v1/webhooks', [
            'url'    => $url,
            'header' => ['title' => $header, 'value' => $secret],
        ]);

        if (!$response->successful()) {
            $this->error('Failed to register webhook:');
            $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));
            return self::FAILURE;
        }

        $data = $response->json();
        $this->info('Webhook registered successfully!');
        $this->table(['ID', 'URL', 'Header'], [
            [$data['id'] ?? '-', $data['url'] ?? '-', $data['header']['title'] ?? '-'],
        ]);

        $this->newLine();
        $this->line('Now set in your .env:');
        $this->line('  TABBY_WEBHOOK_VERIFY_SIGNATURE=true');

        return self::SUCCESS;
    }

    private function listWebhooks(): int
    {
        $response = $this->apiRequest('GET', '/api/v1/webhooks');

        if (!$response->successful()) {
            $this->error('Failed to list webhooks:');
            $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));
            return self::FAILURE;
        }

        $webhooks = $response->json();

        if (empty($webhooks)) {
            $this->info('No webhooks registered.');
            return self::SUCCESS;
        }

        $rows = array_map(fn($w) => [
            $w['id'] ?? '-',
            $w['url'] ?? '-',
            $w['header']['title'] ?? '-',
        ], $webhooks);

        $this->table(['ID', 'URL', 'Header'], $rows);
        return self::SUCCESS;
    }

    private function delete(): int
    {
        $id = $this->option('id');

        if (!$id) {
            $this->error('Please provide the webhook ID with --id=<webhook-id>');
            $this->line('Run: php artisan tabby:webhook list  to see registered webhooks');
            return self::FAILURE;
        }

        $response = $this->apiRequest('DELETE', "/api/v1/webhooks/{$id}");

        if (!$response->successful()) {
            $this->error("Failed to delete webhook {$id}:");
            $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));
            return self::FAILURE;
        }

        $this->info("Webhook {$id} deleted successfully.");
        return self::SUCCESS;
    }

    private function apiRequest(string $method, string $endpoint, array $data = [])
    {
        $baseUrl    = config('tabby.base_url', 'https://api.tabby.sa/api/v2');
        $secretKey  = config('tabby.secret_key');
        $merchantCode = config('tabby.merchant_code');

        // Webhook management API is v1, not v2
        $apiBase = str_replace('/api/v2', '', $baseUrl);

        return Http::withHeaders([
            'Authorization'  => "Bearer {$secretKey}",
            'X-Merchant-Code' => $merchantCode,
            'Content-Type'   => 'application/json',
        ])->{strtolower($method)}($apiBase . $endpoint, $data);
    }

    private function invalidAction(string $action): int
    {
        $this->error("Unknown action '{$action}'. Use: register, list, or delete");
        return self::FAILURE;
    }
}
