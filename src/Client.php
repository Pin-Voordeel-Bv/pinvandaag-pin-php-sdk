<?php

namespace PinVandaag\SDK;

use PinVandaag\SDK\Exceptions\PinVandaagException;
use PinVandaag\SDK\Utils\Logger;

class Client
{
    private string $apiKey;
    private string $terminalId;
    private string $baseUrl;
    private Logger $logger;

    public function __construct(string $apiKey, string $terminalId, string $baseUrl = "https://rest-api.pinvandaag.com")
    {
        $this->apiKey = $apiKey;
        $this->terminalId = $terminalId;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->logger = new Logger();
    }

    public function setBaseUrl(string $url): void
    {
        $this->baseUrl = rtrim($url, '/');
    }

    public function request(string $endpoint, array $data = []): array
    {
        $payload = array_merge([
            "terminalId" => $this->terminalId,
            "key" => $this->apiKey
        ], $data);

        $url = $this->baseUrl . $endpoint;

        $this->logger->log("REQUEST", $payload);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new PinVandaagException("Curl error: " . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Handle 204 success (important for cancel)
        if ($httpCode === 204) {
            return ["status" => 204];
        }

        if ($httpCode >= 400) {
            throw new PinVandaagException("HTTP error: " . $httpCode);
        }

        if (empty($response)) {
            return ["status" => "empty"];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PinVandaagException("Invalid JSON: " . $response);
        }

        $this->logger->log("RESPONSE", $decoded);

        return $decoded;
    }
}