<?php

namespace PinVandaag\SDK\Utils;

use PinVandaag\SDK\Exceptions\PinVandaagException;

class Fallback
{
    public static function execute(callable $primary, callable $fallback, Logger $logger)
    {
        try {
            return $primary();
        } catch (\Exception $e) {
            $logger->log("FALLBACK_TRIGGERED", $e->getMessage());
            return $fallback();
        }
    }
}