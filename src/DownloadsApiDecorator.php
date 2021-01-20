<?php
namespace Germania\DownloadsApi;


/**
 * Use this abstract class as base class for any decorator.
 */
class DownloadsApiDecorator extends DownloadsApiAbstract
{


    /**
     * @var \Germania\DownloadsApi\DownloadsApiInterface
     */
    protected $client;



    /**
     * @param \Germania\DownloadsApi\DownloadsApiInterface $client DownloadsApi client decoratee
     */
    public function __construct( DownloadsApiInterface $client )
    {
        $this->setClient( $client );
    }


    /**
     * Sets the DownloadsApi client decoratee.
     *
     * @param \Germania\DownloadsApi\DownloadsApiInterface $client DownloadsApi client
     */
    public function setClient( DownloadsApiInterface $client ) : self
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
     */
    public function request( string $path, array $filters = array()  ) : iterable
    {
        return $this->client->request($path, $filters );
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
