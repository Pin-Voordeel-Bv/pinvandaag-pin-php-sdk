<?php

namespace PinVandaag;

class TransactionsList
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Haal een transactielijst op.
     *
     * @param int $limit 1 t/m 100
     * @param int $page vanaf 1
     * @param int|null $dateFrom Unix timestamp
     * @param int|null $dateTo Unix timestamp
     * @return array
     */
    public function get(
        int $limit = 100,
        int $page = 1,
        ?int $dateFrom = null,
        ?int $dateTo = null
    ): array {
        if ($limit < 1 || $limit > 100) {
            return [
                'status' => 'error',
                'message' => 'Limit moet tussen 1 en 100 liggen.',
            ];
        }

        if ($page < 1) {
            return [
                'status' => 'error',
                'message' => 'Page moet minimaal 1 zijn.',
            ];
        }

        if ($dateFrom !== null && $dateTo !== null && $dateTo < $dateFrom) {
            return [
                'status' => 'error',
                'message' => 'dateto mag niet eerder zijn dan datefrom.',
            ];
        }

        $terminalId = $this->client->getTerminalId();

        $endpoint = sprintf(
            '/V2/instore/transactions/%s/%d/%d/',
            rawurlencode($terminalId),
            $limit,
            $page
        );

        $query = [];

        if ($dateFrom !== null) {
            $query['datefrom'] = $dateFrom;
        }

        if ($dateTo !== null) {
            $query['dateto'] = $dateTo;
        }

        $response = $this->client->requestWithFallback($endpoint, $query, 'GET');

        if (($response['status'] ?? 'success') !== 'success') {
            return [
                'status' => 'error',
                'message' => $response['message'] ?? 'Transacties ophalen mislukt.',
                'page' => $response['page'] ?? $page,
                'limit' => $response['limit'] ?? $limit,
                'transactions' => [],
                'data' => $response,
            ];
        }

        $transactions = $response['transactions'] ?? [];

        // Normalize amounts
        foreach ($transactions as &$t) {
            if (isset($t['amount'])) {
                $t['amount_euro'] = ((float)$t['amount']) / 100;
            } else {
                $t['amount_euro'] = null;
            }
        }
        unset($t);

        return [
            'status' => $response['status'] ?? 'success',
            'message' => $response['message'] ?? 'Transacties succesvol opgehaald.',
            'page' => $response['page'] ?? $page,
            'limit' => $response['limit'] ?? $limit,
            'transactions' => is_array($transactions) ? $transactions : [],
            'data' => $response,
        ];
    }

    /**
     * Haal alle pagina's op tot er geen transacties meer zijn.
     */
    public function getAll(
        int $limit = 100,
        ?int $dateFrom = null,
        ?int $dateTo = null
    ): array {
        $page = 1;
        $allTransactions = [];

        $maxPages = 1000;

        while ($page <= $maxPages) {
            $result = $this->get($limit, $page, $dateFrom, $dateTo);

            if (($result['status'] ?? 'error') !== 'success') {
                return [
                    'status' => 'error',
                    'message' => $result['message'] ?? 'Ophalen transacties mislukt.',
                    'transactions' => $allTransactions,
                    'count' => count($allTransactions),
                    'data' => $result,
                ];
            }

            $pageTransactions = $result['transactions'] ?? [];

            if (empty($pageTransactions)) {
                break;
            }

            foreach ($pageTransactions as $transaction) {
                $allTransactions[] = $transaction;
            }

            if (count($pageTransactions) < $limit) {
                break;
            }

            $page++;
        }

        return [
            'status' => 'success',
            'message' => 'Transacties succesvol opgehaald.',
            'transactions' => $allTransactions,
            'count' => count($allTransactions),
        ];
    }
}