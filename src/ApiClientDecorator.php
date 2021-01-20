<?php
namespace Germania\DownloadsApiClient;


/**
 * Use this abstract class as base class for any decorator.
 */
class ApiClientDecorator extends ApiClientAbstract
{


    /**
     * @var \Germania\DownloadsApiClient\ApiClientInterface
     */
    protected $client;



    /**
     * @param \Germania\DownloadsApiClient\ApiClientInterface $client DownloadsApi client decoratee
     */
    public function __construct( ApiClientInterface $client )
    {
        $this->setClient( $client );
    }


    /**
     * Sets the DownloadsApi client decoratee.
     *
     * @param \Germania\DownloadsApiClient\ApiClientInterface $client DownloadsApi client
     */
    public function setClient( ApiClientInterface $client ) : self
    {
        $this->client = $client;
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function getAuthentication() : string
    {
        return $this->client->getAuthentication();
    }


    /**
     * @inheritDoc
     *
     * @param  array  $filters
     */
    public function all( array $filters = array() ) : iterable
    {
        return $this->client->all($filters );
    }


    /**
     * @inheritDoc
     *
     * @param  array $filters
     */
    public function latest( array $filters = array() ) : iterable
    {
        return $this->client->latest($filters );
    }


}
