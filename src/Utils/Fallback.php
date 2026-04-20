<?php

namespace PinVandaag\Utils;

class Fallback
{
    private static function shouldFallback(string $message): bool
    {
        return
            str_contains($message, 'Curl error') ||
            str_contains($message, 'timed out') ||
            str_contains($message, 'Failed to connect') ||
            str_contains($message, 'Could not resolve host') ||
            str_contains($message, 'HTTP error: 500') ||
            str_contains($message, 'HTTP error: 502') ||
            str_contains($message, 'HTTP error: 503') ||
            str_contains($message, 'HTTP error: 504');
    }

    public static function execute(callable $primary, callable $fallback, ?Logger $logger = null)
    {
        try {
            return $primary();
        } catch (\Throwable $e) {
            $message = $e->getMessage();

            if (!self::shouldFallback($message)) {
                throw $e;
            }

            if ($logger !== null) {
                $logger->log('FALLBACK_TRIGGERED', [
                    'error' => $message,
                ]);
            }

            return $fallback();
        }
    }
}