<?php

namespace PinVandaag\Utils;

class Status
{
    public static function normalize(string $status): string
    {
        switch (strtolower($status)) {
            case 'success':
            case 'paid':
            case 'betaald':
                return 'success';

            case 'pending':
                return 'pending';

            case 'failed':
            case 'geweigerd':
            case 'geannuleerd':
                return 'failed';

            default:
                return 'unknown';
        }
    }
}