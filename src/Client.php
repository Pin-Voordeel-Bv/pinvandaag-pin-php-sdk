<?php

namespace PinVandaag;

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

    public function getTerminalId(): string
    {
        return $this->terminalId;
    }

    public function request(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        $url = $this->baseUrl . $endpoint;
        $method = strtoupper($method);

        if ($method === 'GET' && !empty($data)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($data);
        }

        $this->logger->log("REQUEST", [
            'method' => $method,
            'url' => $url,
            'data' => $data,
        ]);

        $ch = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . $this->apiKey,
                'Accept: application/json',
            ],
        ];

        if ($method === 'POST') {
            $payload = array_merge([
                'terminal_id' => $this->terminalId,
            ], $data);

            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($payload);
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 204) {
            return ['status' => 204];
        }

        if ($httpCode >= 400) {
            throw new \Exception('HTTP error: ' . $httpCode . ' response: ' . $response);
        }

        if ($response === '' || $response === null) {
            return ['status' => 'empty'];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON: ' . $response);
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