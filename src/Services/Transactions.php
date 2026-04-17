<?php

namespace PinVandaag\SDK\Services;

use PinVandaag\SDK\Client;
use PinVandaag\SDK\Utils\Status;

class Transactions
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function create(int $amount): array
    {
        return $this->client->request("/V2/instore/transactions/create", [
            "amount" => $amount
        ]);
    }

    public function createEuro(float $amount): array
    {
        return $this->create((int) round($amount * 100));
    }

    public function refund(int $amount): array
    {
        return $this->client->request("/V2/instore/transactions/refund", [
            "amount" => $amount
        ]);
    }

    public function status(string $transactionId): array
    {
        $response = $this->client->request("/V2/instore/transactions/status", [
            "transaction_id" => $transactionId
        ]);

        if (isset($response['status'])) {
            $response['normalizedStatus'] = Status::normalize($response['status']);
        }

        return $response;
    }

    public function cancel(string $transactionId): bool
    {
        $response = $this->client->request("/V2/instore/transactions/cancel", [
            "transaction_id" => $transactionId
        ]);

        return in_array($response['status'] ?? null, ['success', 'stopped', 204], true);
    }
}