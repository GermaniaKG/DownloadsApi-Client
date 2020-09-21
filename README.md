<img src="https://static.germania-kg.com/logos/ga-logo-2016-web.svgz" width="250px">

------



# DownloadsApi Client

**Server-side PHP client for retrieving a list of available downloads from Germania's DownloadsApi.**

[![Packagist](https://img.shields.io/packagist/v/germania-kg/downloadsapi-client.svg?style=flat)](https://packagist.org/packages/germania-kg/downloadsapi-client)
[![PHP version](https://img.shields.io/packagist/php-v/germania-kg/downloadsapi-client.svg)](https://packagist.org/packages/germania-kg/downloadsapi-client)
[![Build Status](https://img.shields.io/travis/GermaniaKG/DownloadsApi-Client.svg?label=Travis%20CI)](https://travis-ci.org/GermaniaKG/DownloadsApi-Client)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/GermaniaKG/DownloadsApi-Client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/DownloadsApi-Client/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/GermaniaKG/DownloadsApi-Client/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/DownloadsApi-Client/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/GermaniaKG/DownloadsApi-Client/badges/build.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/DownloadsApi-Client/build-status/master)


## Installation

```bash
$ composer require germania-kg/downloadsapi-client
```



## Usage

The *DownloadsApiClient* requires either a **Guzzle Client** or **PSR-18 HTTP Client**. 

### The Guzzle Factory

The *DownloadsApiClient* requires a **Guzzle Client** which will perform the API requests. The Guzzle client can be obtained from **GuzzleFactory**, using the *DownloadsApi* *endpoint* and the *AuthApi Access token*.

```php
<?php
use Germania\DownloadsApiClient\GuzzleFactory;

// Have your DownloadsAPI endpoint and Access token at hand
$api = "https://api.example.com/"
$token = "manymanyletters"; 

// Setup a Guzzle Client that will ask Downloads API
$guzzle = (new GuzzleFactory)( $api, $token);
```

### The HTTP Client Factory

Creates a `Http\Adapter\Guzzle6\Client ` which implements PSR-18's `Psr\Http\Client\ClientInterface`. See more in PHP-HTTP's [Guzzle 6 Adapter](http://docs.php-http.org/en/latest/clients/guzzle6-adapter.html) documentation.

```php
<?php
use Germania\DownloadsApiClient\HttpClientFactory;

// Have your DownloadsAPI endpoint and Access token at hand
$api = "https://api.example.com/"
$token = "manymanyletters"; 

// Create Guzzle Client that will ask Downloads API
$http_client = (new HttpClientFactory)( $api, $token);
```



### The DownloadsApiClient

The **DownloadsApiClient** requires the above *Guzzle Client* as well as a *PSR-6 Cache ItemPool.* It optionally accepts a *PSR-3 Logger* and/or a PSR-3 *Loglevel names* for both error and success cases.

```php
<?php
use Germania\DownloadsApiClient\DownloadsApiClient;
use Germania\DownloadsApiClient\GuzzleFactory;

$guzzle = (new GuzzleFactory)( $api, $token);
$cache  = new \Stash\Pool( ... );
$logger = new \Monolog\Logger( ... );

$client = new DownloadsApiClient($guzzle, $cache );
$client = new DownloadsApiClient($guzzle, $cache, $logger );
$client = new DownloadsApiClient($guzzle, $cache, $logger, "alert" );
$client = new DownloadsApiClient($guzzle, $cache, $logger, "error", "info" );
```



### Security considerations: The caching engine

The results are stored in the PSR-6 cache passed to the *DownloadsApiClient* constructor, using a *cache key* to look up the results next time. 

This *cache key* contains a fast-to-compute **sha256 hash** of the authorization header. The downside is, your auth tokens are vulnerable to *hash collision* attacks, when two different string produce the same hash. 

The auth token hopefully has a baked-in lifetime. Once this lifetime is reached, the auth token is worthless anyway. And, when an attacker has file access to your cache, he will have all results, regardless if he has your auth tokens or not. 

**Security tips:**

- Consider to pass an “Always-empty-cache” or one with very short lifetime, such as [Stash's Ephemeral](http://www.stashphp.com/Drivers.html#ephemeral) driver.
- Store your cache securely. This is not responsibility of this library.
- Clean your downloads cache often. This is not responsibility of this library.





### Retrieve documents

The **DownloadsApiClient** provides two public methods, ***all*** and ***latest***, which return an ***ArrayIterator*** with the documents provided by *Germania's DownloadsAPI*. 

The resulting documents list will have been pre-filtered according to the permissions related with the Access token sent along with the *Guzzle Client* request.

**Caching:** The results are cached in the given *PSR-6 Cache Item Pool*. The cache item TTL depends on the `Cache-Control: max-age=SECONDS` header that came along the response to the *Guzzle Client* request. The default TTL is 3600 seconds. 

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
  'brand'   => "luxaflex",
  'category' => "brochure",
  'language' => "en",
  'keyword' => "customers,retailers",
  'product' => "cars,bikes"
);

$downloads = $downloads_client->latest($filters);
$downloads = $downloads_client->all($filters);
```





## Errors and Exceptions

```php
<?php
use Germania\DownloadsApiClient\{
  DownloadsApiClientExceptionInterface,
	DownloadsApiClientRuntimeException,
  DownloadsApiClientUnexpectedValueException
};
```



### Exceptions on instantiation

When the *Guzzle* client provided to the *DownloadsApiClient* lacks an `Authorization` header, a **DownloadsApiClientRuntimeException** will be thrown. This class implements `DownloadsApiClientExceptionInterface` and extends `\RuntimeException`. 

### Exceptions during request

Just in case the *DownloadsApiClient* (to be exact: the Guzze client) receives a *Guzzle* *[**RequestException**](http://docs.guzzlephp.org/en/stable/quickstart.html#exceptions)*, i.e. something wrong with the request or on the server, both the *all* and *latest* methods will return an **empty ArrayIterator**.  The error will be logged to the *PSR-3 Logger* passed to the constructor.

**Please note:**
*Guzzle* [**TransferExceptions**](http://docs.guzzlephp.org/en/stable/quickstart.html#exceptions) – thrown while transferring requests – will not be caught internally and instead will bubble up.

### Unexpected values in response

Whenever the response can't be decoded to a useful array, a  **DownloadsApiClientUnexpectedValueException** will be thrown. This class implements `DownloadsApiClientExceptionInterface` and extends `\UnexpectedValueException`. 





## Issues

See [full issues list.][i0]

[i0]: https://github.com/GermaniaKG/DownloadsApi-Client/issues



## Development

```bash
$ git clone https://github.com/GermaniaKG/DownloadsApi-Client.git
$ cd DownloadsApi-Client
$ composer install
```



## Unit tests

Copy `phpunit.xml.dist` to `phpunit.xml` and adapt the **DOWNLOADS_API** and **AUTH_TOKEN** environment variables. Then run [PhpUnit](https://phpunit.de/) like this:

```bash
$ composer test
# or
$ vendor/bin/phpunit
```

