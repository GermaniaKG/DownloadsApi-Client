# Germania KG · DownloadsApi Client



## Installation

```bash
$ composer require germania-kg/downloadsapi-client
```



## Usage

### Setup the client

```php
<?php
use Germania\DownloadsApiClient\DownloadsApiClient;
use GuzzleHttp\Client;

// Setup a Guzzle Client that will ask Downloads API
$guzzle = new Client( ... );

// Have you Downloads API Access token at hand
$token = "manymanyletters"; 

// Setup the Downloads API client, 
// optionally with PSR-3 Logger (recommended))
$downloads_client = new DownloadsApiClient($token, $guzzle );
$downloads_client = new DownloadsApiClient($token, $guzzle, $logger );

```

### Retrieve documents

```php
$downloads = $downloads_client->latest();
$downloads = $downloads_client->all();

foreach( $downloads as $document):
	print_r( $document );
endforeach;
```

### Filtering results

```php
$filters = array(
  'company' => "ACME",
  'category' => "brochure",
  'language' => "en",
  'keyword' => "customers",
  'product' => "cars"
);

$downloads = $downloads_client->latest($filters);
$downloads = $downloads_client->all($filters);
```

## Testing

Copy `phpunit.xml.dist` to `phpunit.xml` and adapt the **DOWNLOADS_API** and **AUTH_TOKEN** environment variables. Then run *Phpunit* like this:

```bash
$ composer phpunit
```

