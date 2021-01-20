<?php
namespace Germania\DownloadsApi;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;


/**
 * Factory to creates a Guzzle PSR-18 HTTP Client
 * and PSR-7 Request templates
 * for usage with Germania KG's Downloads API.
 */
interface FactoryInterface
{


    /**
     * @return ClientInterface
     */
    public function createClient() : ClientInterface;


    /**
     * @return RequestInterface
     */
    public function createRequest( string $api, string $token ) : RequestInterface;
}
