<?php

namespace PinVandaag\Services;

use PinVandaag\Client;
use PinVandaag\Utils\Status;

class Transactions
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function create(int $amount): array
    {
        if (empty($amount)) {
            return $this->client->error('Bedrag is verplicht.');
        }

        return $this->client->requestWithFallback("/V2/instore/transactions/start", [
            "amount" => $amount
        ]);
    }

    public function createEuro(float $amount): array
    {
        return $this->create((int) round($amount * 100));
    }

    public function refund(int $amount): array
    {
        return $this->client->requestWithFallback("/V2/instore/transactions/refund", [
            "amount" => $amount
        ]);
    }

    public function status(string $transactionId): array
    {
        $response = $this->client->requestWithFallback(
            "/V2/instore/transactions/status",
            ["transaction_id" => $transactionId]
        );

        // 🔥 EXTRA: if transaction not found → force backup
        if (
            isset($response['status']) &&
            in_array(strtolower($response['status']), ['not_found', 'unknown'], true)
        ) {
            $originalBaseUrl = $this->client->getBaseUrl();
            $this->client->setBaseUrl("https://api-backup.pinvandaag.com");

            try {
                $response = $this->client->request(
                    "/V2/instore/transactions/status",
                    ["transaction_id" => $transactionId]
                );
            } finally {
                $this->client->setBaseUrl($originalBaseUrl);
            }
        }

        if (isset($response['status'])) {
            $response['normalizedStatus'] = Status::normalize($response['transaction']['status']);
        }

        return $response;
    }

    public function cancel(string $transactionId): bool
    {
        $response = $this->client->requestWithFallback("/V2/instore/transactions/stop", [
            "transaction_id" => $transactionId
        ]);

        return in_array($response['status'] ?? null, ['success', 'stopped', 204], true);
    }
}