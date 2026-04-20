### Usage example

```
use PinVandaag\Client;
use PinVandaag\Services\Transactions;

require 'vendor/autoload.php';

$client = new Client("API_KEY", "TERMINAL_ID");

// Optional backup API
// $client->setBaseUrl("https://backup-api.pinvandaag.com");

$transactions = new Transactions($client);

// Create €1.00
$response = $transactions->createEuro(1.00);

$transactionId = $response['transactionId'] ?? null;

$status = $transactions->status($transactionId);

if ($status['normalizedStatus'] === 'success') {
    echo "Payment OK";
}
```

### Usage webhook

```
use PinVandaag\Client;
use PinVandaag\Webhook\WebhookHandler;

require 'vendor/autoload.php';

$client = new Client("API_KEY", "TERMINAL_ID");

$handler = new WebhookHandler($client);
$handler->handle();
```

### Usage CTMP

```
use PinVandaag\Client;
use PinVandaag\CTMP;

$client = new Client($apiKey, $terminalId);
$ctmp = new CTMP($client, 'Worldline');

$result = $ctmp->send();

if ($result['status'] === 'success') {
    echo $result['message'];
} else {
    echo $result['message'];
}
```

### Usage TransactionsList

```
// get some transactions
use PinVandaag\Client;
use PinVandaag\TransactionsList;

$client = new Client($apiKey, $terminalId);

$transactionsList = new TransactionsList($client);

$result = $transactionsList->get(
    100,
    1,
    strtotime('2026-04-14 00:00:00'),
    strtotime('2026-04-14 23:59:59')
);

print_r($result);

// get all transactions
$result = $transactionsList->getAll(
    100,
    strtotime('2026-04-14 00:00:00'),
    strtotime('2026-04-14 23:59:59')
);
```

### Usage LastTransaction

```
use PinVandaag\Client;
use PinVandaag\LastTransaction;

$client = new Client($apiKey, $terminalId);
$lastTransaction = new LastTransaction($client);

$result = $lastTransaction->get();

if ($result['status'] === 'success') {
    print_r($result['transaction']);
} else {
    echo $result['message'];
}
```