### Usage example

```
use PinVandaag\SDK\Client;
use PinVandaag\SDK\Services\Transactions;

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