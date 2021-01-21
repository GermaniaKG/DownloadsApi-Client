<img src="https://static.germania-kg.com/logos/ga-logo-2016-web.svgz" width="250px">

------



# DownloadsApi Client

**Server-side PHP client for retrieving a list of available downloads from Germania's Downloads API.**

[![Packagist](https://img.shields.io/packagist/v/germania-kg/downloadsapi-client.svg?style=flat)](https://packagist.org/packages/germania-kg/downloadsapi-client)
[![PHP version](https://img.shields.io/packagist/php-v/germania-kg/downloadsapi-client.svg)](https://packagist.org/packages/germania-kg/downloadsapi-client)
[![Build Status](https://img.shields.io/travis/GermaniaKG/DownloadsApi-Client.svg?label=Travis%20CI)](https://travis-ci.org/GermaniaKG/DownloadsApi-Client)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/GermaniaKG/DownloadsApi-Client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/DownloadsApi-Client/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/GermaniaKG/DownloadsApi-Client/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/DownloadsApi-Client/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/GermaniaKG/DownloadsApi-Client/badges/build.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/DownloadsApi-Client/build-status/master)

## Installation

The v5 release is a complete rewrite, so there is no “upgrading” procedure.

```bash
$ composer require germania-kg/downloadsapi-client
$ composer require germania-kg/downloadsapi-client:^5.0
```

This package requires a *PSR-18 HTTP client* implementation and a *PSR-17 HTT factory* implementation. Suggestions are [Guzzle 7](https://packagist.org/packages/guzzlehttp/guzzle) via [guzzlehttp/guzzle](https://packagist.org/packages/guzzlehttp/) and Nyholm's [nyholm/psr7](nyholm/psr7) which (despite its name) provides the PSR-17 factories as well:

```bash
$ composer require nyholm/psr7
$ composer require guzzlehttp/guzzle
```



## Basics

### Interface and abstract class

Interface **DownloadsApiInterface** provides public methods for retrieving documents, ***all*** and ***latest*** and ***request***, with the latter for internal use. All of these return an **iterable**. There are also interceptors for the authentication key:

```php
// Provided by interface 
// Germania\DownloadsApi\DownloadsApiInterface

public function all() : iterable;
public function latest() : iterable;
public function request( string $path ) : iterable ;

public function setAuthentication( ?string $key ) : DownloadsApiInterface;
public function getAuthentication( ) : string;
```

Abstract class **DownloadsApiAbstract** prepares the *all* and *latest* methods to delegate directly to the *request* method. It also utilizes various useful traits such as `Psr\Log\LoggerAwareTrait`, `LoglevelTrait` and `AuthenticationTrait`. – So any class extending this abstract will thus provide:

```php
// Inherited from abstract class 
// Germania\DownloadsApi\DownloadsApiAbstract
  
$api->setLogger( \Monolog\Logger( ... ) ); 

$api->setErrorLoglevel( \LogLevel::ERROR );
$api->setSuccessLoglevel( \LogLevel::INFO );

$api->setAuthentication( "secrete" );
$api->getAuthentication(); // "secret"
```



### PSR-6 Cache Support

Class **CacheDownloadsApiDecorator** wraps an existing *DownloadsApi* instance and adds support for *PSR-6 Caches*. It extends *DownloadsApiDecorator* which itself extends *DownloadsApiAbstract*, so the class also implements *DownloadsApiInterface*.

The constructor requires a ***DownloadsApi*** (or *DownloadsApiInterface)* instance and a ***PSR-6 Cache Item Pool***. You may optionally pass a ***cache lifetime*** in seconds which defaults to 14400 (4 hours).

```php
<?php
use Germania\DownloadsApi\DownloadsApi;
use Germania\DownloadsApi\CacheDownloadsApiDecorator;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$api = new DownloadsApi( ... );
$psr6 = new FilesystemAdapter( ... );

$cached_api = new CacheDownloadsApiDecorator( $api, $psr6);
$cached_api = new CacheDownloadsApiDecorator( $api, $psr6, 14400);
```



### The API client

The **DownloadsApi** API client extends *DownloadsApiAbstract* und thus implements *DownloadsApiInterface*. The constructor requires a *PSR-18 HTTP Client*, a *PSR-17 Request factory* and an *API key*. – To obtain an API key, ask the web developers over at Germania KG.

```php
<?php
use Germania\DownloadsApi\DownloadsApi;
use GuzzleHttp\Client;
use Nyholm\Psr7\Factory\Psr17Factory;

$psr18 = new Client;
$psr17 = new Psr17Factory;
$key = "secret"
  
$api = new DownloadsApi($psr18, $psr17, $key);
```



## Retrieve documents

Public methods ***all*** and ***latest*** return an ***Array* iterable** with the documents provided by Germania's Documents API. 

```php
$downloads = $api->latest();
$downloads = $api->all();

foreach( $downloads as $document):
	print_r( $document );
endforeach;
```

### Example record

The above `print_r( $document )` will reveal something like this:

```text
Array (
    [company] =>
    [brand] => mybrand
    [title] => Brand<sup>®</sup> Window Styling
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
  'brand'   => "mybrand",
  'category' => "brochure",
  'language' => "en",
  'keyword' => "customers,retailers",
  'product' => "cars,bikes"
);

$downloads = $api->latest($filters);
$downloads = $api->all($filters);
```



## Errors and Exceptions

It should be plenty to watch out for `\Germania\DownloadsApi\Exceptions\DownloadsApiExceptionInterface` since all concrete exception classes implement this interface.

```php
<?php
use Germania\DownloadsApi\DownloadsApiExceptionInterface;
use Germania\DownloadsApi\DownloadsApiRuntimeException;
use Germania\DownloadsApi\DownloadsApiResponseException;
use Germania\DownloadsApi\DownloadsApiUnexpectedValueException;

try {
  $client->latest();
}
catch (\Germania\DownloadsApi\DownloadsApiExceptionInterface $e) {
	echo $e->getMessage();
}
```

**Exceptions during request:**
Whenever a PSR-18 client request fails, a **DownloadsApiRuntimeException** will be thrown. This class implements `DownloadsApiExceptionInterface` and extends `\RuntimeException`.

**HTTP Error responses:**
When a API call returns an HTTP error response, a **DownloadsApiResponseException** will be thrown. This class implements `DownloadsApiExceptionInterface` and extends `\RuntimeException`.

**Unexpected values in response:**
Whenever a response (even with status 200 OK) can't be decoded to an useful array, a  **DownloadsApiUnexpectedValueException** will be thrown. This class implements `DownloadsApiExceptionInterface` and extends `\UnexpectedValueException`.



## Unit tests and development

Copy `phpunit.xml.dist` to `phpunit.xml` and fill in the **Authentication data** you obtained from Germania. 

- `AUTH_TOKEN` when you've got an auth token for the Downloads API at hand.
- Otherwise, fill in `AUTH_API`, `AUTH_USER`, and `AUTH_PASS` . The auth token will then be fetched from Germania's Auth API before each test.

In order to run unit tests, run [PhpUnit](https://phpunit.de/) like this:

```bash
$ composer test
# or
$ vendor/bin/phpunit
```



## Issues

See [full issues list.][i0]

[i0]: https://github.com/GermaniaKG/DownloadsApi-Client/issues



## 