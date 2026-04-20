<?php

namespace PinVandaag\Utils;

class Receipt
{
    public static function parse(?string $receipt): array
    {
        if (!$receipt) return [];

        $decoded = json_decode($receipt, true);

        if (!is_array($decoded)) return [];

        return array_map(function ($line) {
            if (is_array($line)) {
                return $line[1] ?? '';
            }
            return $line;
        }, $decoded);
    }
}