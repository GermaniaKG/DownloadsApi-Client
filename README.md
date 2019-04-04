# Germania KG · DownloadsApi Client

**Server-side PHP client for retrieving a list of available downloads from Germania's DownloadsApi.**



## Installation

```bash
$ composer require germania-kg/downloadsapi-client
```



## Usage

### Setup the client

The **DownloadsApiClient** requires an *Access token* as well as a *Guzzle Client* which will perform the API requests. It optionally accepts a *PSR-3 Logger* instance which is highly recommended.

Germania's *DownloadsApi* will use the Access token to pre-filter the results according to the permissions related with this Access token.

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

The **DownloadsApiClient** provides two public methods, ***all*** and ***latest***, which return an *ArrayIterator* with the documents provided by  *Germania's DownloadsAPI*.

```php
$downloads = $downloads_client->latest();
$downloads = $downloads_client->all();

foreach( $downloads as $document):
	print_r( $document );
endforeach;
```



### Filtering results

To narrow down the results, both the *all* and *latest* methods accept an **array with filter values.** The fiter values may contain multiple values, separated with comma. 

Think of the filter array items as `WHERE … AND…` clauses, and comma-separated values as `'a' OR 'b'`

```php
$filters = array(
  'company' => "ACME",
  'category' => "brochure",
  'language' => "en",
  'keyword' => "customers,retailers",
  'product' => "cars,bikes"
);

$downloads = $downloads_client->latest($filters);
$downloads = $downloads_client->all($filters);
```



## Errors and Exceptions

### Guzzle Exceptions

Just in case the *DownloadsApiClient* (to be exact: the Guzze client) receives a *Guzzle* *[**RequestException**](http://docs.guzzlephp.org/en/stable/quickstart.html#exceptions)*, i.e. something wrong with the request or on the server, both the *all* and *latest* methods will return an **empty ArrayIterator**.  The error will be logged to the *PSR-3 Logger* passed to the constructor.

**Please note:**
*Guzzle* [**TransferExceptions**](http://docs.guzzlephp.org/en/stable/quickstart.html#exceptions) – thrown while transferring requests – will not be caught internally and instead will bubble up.

### Unexpected values in response

Whenever the response can't be decoded to a useful array, a  **DownloadsApiClientUnexpectedValueException** will be thrown. This class implements `DownloadsApiClientExceptionInterface` and extends `\UnexpectedValueException`. 



## Testing

Copy `phpunit.xml.dist` to `phpunit.xml` and adapt the **DOWNLOADS_API** and **AUTH_TOKEN** environment variables. Then run *Phpunit* like this:

```bash
$ composer phpunit
```

