<?php

namespace PinVandaag;

class CTMP
{
    protected Client $client;
    protected ?string $terminalType;

    public function __construct(Client $client, ?string $terminalType = null)
    {
        $this->client = $client;
        $this->terminalType = $terminalType;
    }

    /**
     * Send a CTMP request to a Worldline terminal.
     *
     * CTMP is only supported for Worldline terminals.
     */
    public function send(): array
    {
        if (
            $this->terminalType !== null &&
            strtolower($this->terminalType) !== 'worldline'
        ) {
            return $this->client->error("CTMP is alleen beschikbaar voor Worldline terminals.");
        }

        $response = $this->client->requestWithFallback(
            '/V2/instore/transactions/ctmp',
            [],
            'POST'
        );

        $status = $response['status'] ?? null;
        $message = $response['message'] ?? null;

        if ($status === 'success') {
            return $this->client->success($message ?: "CTMP verzoek succesvol verzonden naar terminal.", $response);
        }

        if ($status === 204 || $status === '204') {
            return $this->client->success("CTMP verzoek succesvol verzonden naar terminal.", $response);
        }

        if ($status === 'error') {
            return $this->client->error($message ?: "CTMP verzoek mislukt.", $response);
        }

        return $this->client->error($message ?: "Onbekende response bij CTMP verzoek.", $response);
    }
}