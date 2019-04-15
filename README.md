# Germania KG · DownloadsApi Client

**Server-side PHP client for retrieving a list of available downloads from Germania's DownloadsApi.**



## Installation

```bash
$ composer require germania-kg/downloadsapi-client
```



## Usage

### The Guzzle Factory

The *DownloadsApiClient* requires a **Guzzle Client** which will perform the API requests. The Guzzle client can be obtained from **GuzzleFactory**, using the *DownloadsApi* *endpoint* and the *AuthApi Access token*.

```php
<?php
use Germania\DownloadsApiClient\GuzzleFactory;

// Have your DownloadsAPI endpoint and Access token at hand
$api = "https://api.example.com/"
$token = "manymanyletters"; 

// Setup a Guzzle Client that will ask Downloads API
$guzzle_factory = new GuzzleFactory;
$guzzle = $guzzle_factory( $api, $token);
```



### The DownloadsApiClient

The **DownloadsApiClient** requires the above *Guzzle Client,* and optionally accepts a *PSR-3 Logger* and/or a PSR-3 *Loglevel name*.

```php
<?php
use Germania\DownloadsApiClient\DownloadsApiClient;

$downloads_client = new DownloadsApiClient($guzzle );
$downloads_client = new DownloadsApiClient($guzzle, $logger );
$downloads_client = new DownloadsApiClient($guzzle, $logger, "alert" );
```



### Retrieve documents

The **DownloadsApiClient** provides two public methods, ***all*** and ***latest***, which return an ***ArrayIterator*** with the documents provided by *Germania's DownloadsAPI*. 

The resulting documents list will have been pre-filtered according to the permissions related with the Access token sent along with the Guzzle Client request.

```php
$downloads = $downloads_client->latest();
$downloads = $downloads_client->all();

foreach( $downloads as $document):
	print_r( $document );
endforeach;
```

#### Example record

The `print_r( $document )` will reveal something like this:

```text
Array (
    [company] =>
    [brand] => luxaflex
    [title] => Luxaflex<sup>®</sup> Dachfenster-Produkte
    [subtitle] =>
    [subtitle2] =>
    [description] =>
    [picture] => Array (
        [src] =>
        [fallback] =>
    )

    [language] => de
    [download] => https://download.example.com/document.pdf
    [categories] => Array(
        [0] => montageanleitung
    )

    [keywords] => Array ()

    [products] => Array (
        [0] => dachflaechen
        [1] => plissee
        [2] => duette
        [3] => jalousie
        [4] => rollo
    )

    [fileSize] => 2298631
    [lastModified] => Array  (
        [date] => 2018-02-19 10:21:14.000000
        [timezone_type] => 2
        [timezone] => GMT
    )

    [daysAgo] => 420
)
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

### Exceptions during request

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

