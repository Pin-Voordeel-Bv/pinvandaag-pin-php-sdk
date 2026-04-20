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
            return [
                "status" => "error",
                "message" => "CTMP is alleen beschikbaar voor Worldline terminals.",
            ];
        }

        $response = $this->client->request(
            '/V2/instore/transactions/ctmp',
            [],
            'POST'
        );

        $status = $response['status'] ?? null;
        $message = $response['message'] ?? null;

        if ($status === 'success') {
            return [
                "status" => "success",
                "message" => $message ?: "CTMP verzoek succesvol verzonden naar terminal.",
                "data" => $response,
            ];
        }

        if ($status === 204 || $status === '204') {
            return [
                "status" => "success",
                "message" => "CTMP verzoek succesvol verzonden naar terminal.",
                "data" => $response,
            ];
        }

        if ($status === 'error') {
            return [
                "status" => "error",
                "message" => $message ?: "CTMP verzoek mislukt.",
                "data" => $response,
            ];
        }

        return [
            "status" => "error",
            "message" => $message ?: "Onbekende response bij CTMP verzoek.",
            "data" => $response,
        ];
    }
}