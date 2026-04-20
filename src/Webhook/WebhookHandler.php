<?php

namespace PinVandaag\Webhook;

use PinVandaag\Client;
use PinVandaag\Utils\Logger;

class WebhookHandler
{
    private Client $client;
    private Logger $logger;
    private string $backupUrl;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->logger = new Logger();
        $this->backupUrl = "https://backup-api.pinvandaag.com/wl.php";
    }

    public function handle(): void
    {
        $input = file_get_contents("php://input");

        $data = json_decode($input, true);

        $this->logger->log("WEBHOOK_RECEIVED", $data);

        if (!$data) {
            http_response_code(400);
            echo "Invalid JSON";
            return;
        }

        $transactionId = $data['transactionId'] ?? $data['id'] ?? null;

        if (!$transactionId) {
            $this->logger->log("WEBHOOK_NO_TRANSACTION_ID", $data);
            return;
        }

        // 🔥 Check if transaction exists locally
        try {
            $status = $this->client->request(
                "/V2/instore/transactions/status",
                ["transaction_id" => $transactionId]
            );

            if (isset($status['status']) && $status['status'] === 'unknown') {
                $this->forwardToBackup($input);
            }

        } catch (\Exception $e) {
            // 🔥 If error → forward
            $this->logger->log("WEBHOOK_ERROR_FORWARD", $e->getMessage());
            $this->forwardToBackup($input);
        }

        echo "OK";
    }

    private function forwardToBackup(string $raw): void
    {
        // prevent loop
        if (str_contains($_SERVER['HTTP_HOST'], 'backup-api')) {
            return;
        }

        $this->logger->log("FORWARDING_TO_BACKUP", $raw);

        $ch = curl_init($this->backupUrl);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $raw,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true
        ]);

        curl_exec($ch);
    }
}