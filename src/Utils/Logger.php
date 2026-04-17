<?php

namespace PinVandaag\SDK\Utils;

class Logger
{
    private string $file;

    public function __construct(string $file = null)
    {
        $this->file = $file ?? __DIR__ . '/../../pinvandaag.log';
    }

    public function log(string $type, $data): void
    {
        file_put_contents(
            $this->file,
            date('Y-m-d H:i:s') . " [$type] " . json_encode($data) . PHP_EOL,
            FILE_APPEND
        );
    }
}