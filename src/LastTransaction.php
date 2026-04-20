<?php

namespace PinVandaag;

class LastTransaction
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Haal de laatste transactie op voor de ingestelde terminal.
     */
    public function get(): array
    {
        try {
            $response = $this->client->requestWithFallback(
                '/V2/instore/transactions/last_transaction',
                [],
                'POST'
            );

            if (empty($response)) {
                return $this->client->error('Geen transactie gevonden.');
            }

            // 404 = terminal niet gevonden of geen transacties
            if (($response['http_code'] ?? null) === 404) {
                return $this->client->error('Terminal niet gevonden of geen transacties beschikbaar.', $response);
            }

            // Als API direct transaction row teruggeeft
            if (isset($response['id']) || isset($response['transaction_id'])) {
                return [
                    'status' => 'success',
                    'message' => 'Laatste transactie succesvol opgehaald.',
                    'transaction' => $this->normalize($response),
                    'data' => $response,
                ];
            }

            // Als API error-shape teruggeeft
            if (($response['status'] ?? null) === 'error') {
                return $this->client->error($this->extractErrorMessage($response), $response);
            }

            return $this->client->error('Onbekende response bij ophalen laatste transactie.', $response);
        } catch (\Throwable $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'HTTP error: 404')) {
                return $this->client->error('Terminal niet gevonden of geen transacties beschikbaar.');
            }

            return $this->client->error($message);
        }
    }

    protected function normalize(array $transaction): array
    {
        return [
            'id' => $transaction['id'] ?? null,
            'status' => $transaction['status'] ?? null,
            'terminal' => $transaction['terminal'] ?? null,
            'amount' => $transaction['amount'] ?? null,
            'amount_euro' => isset($transaction['amount'])
                ? ((float) $transaction['amount']) / 100
                : null,
            'transaction_id' => $transaction['transaction_id'] ?? null,
            'own_reference' => $transaction['own_reference'] ?? '',
            'which_api' => $transaction['which_api'] ?? null,
            'created_at' => $transaction['created_at'] ?? null,
            'updated_at' => $transaction['updated_at'] ?? null,
            'callback_url' => $transaction['callback_url'] ?? '',
            'payment_url' => $transaction['payment_url'] ?? null,
            'receipt' => $transaction['receipt'] ?? null,
            'incident_code' => $transaction['incident_code'] ?? null,
            'error_msg' => $transaction['error_msg'] ?? '',
            'cancel_counter_ccv' => $transaction['cancel_counter_ccv'] ?? null,
        ];
    }

    protected function extractErrorMessage(array $response): string
    {
        $message = $response['message'] ?? null;

        if (is_string($message) && $message !== '') {
            return $message;
        }

        if (is_array($message)) {
            if (!empty($message['error']) && is_string($message['error'])) {
                return $message['error'];
            }

            if (!empty($message['validation_errors']) && is_array($message['validation_errors'])) {
                $parts = [];

                foreach ($message['validation_errors'] as $field => $errors) {
                    if (is_array($errors)) {
                        foreach ($errors as $rule => $errorText) {
                            $parts[] = $field . ': ' . $errorText;
                        }
                    }
                }

                if (!empty($parts)) {
                    return implode(' ', $parts);
                }
            }
        }

        return 'Ophalen van laatste transactie mislukt.';
    }
}