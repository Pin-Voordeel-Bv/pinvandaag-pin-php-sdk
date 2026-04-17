<?php

namespace PinVandaag\SDK;

use PinVandaag\SDK\Exceptions\PinVandaagException;
use PinVandaag\SDK\Utils\Fallback;
use PinVandaag\SDK\Utils\Logger;

class Client
{
    private string $apiKey;
    private string $terminalId;
    private string $baseUrl;
    private Logger $logger;
    private string $backupUrl = "https://backup-api.pinvandaag.com";

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

    public function setBackupUrl(string $url): void
    {
        $this->backupUrl = rtrim($url, '/');
    }

    public function request(string $endpoint, array $data = []): array
    {
        $payload = array_merge([
            "terminal_id" => $this->terminalId,
        ], $data);

        $url = $this->baseUrl . $endpoint;

        $this->logger->log("REQUEST", [
            "url" => $url,
            "payload" => $payload,
        ]);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . $this->apiKey,
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
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
            throw new PinVandaagException("HTTP error: " . $httpCode . " response: " . $response);
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

    public function requestWithFallback(string $endpoint, array $data = []): array
    {
        return Fallback::execute(
            fn() => $this->request($endpoint, $data),
            function () use ($endpoint, $data) {
                $this->logger->log("USING_BACKUP_API", $endpoint);

                $original = $this->baseUrl;
                $this->baseUrl = $this->backupUrl;

                $result = $this->request($endpoint, $data);

                $this->baseUrl = $original;

                return $result;
            },
            $this->logger
        );
    }
}