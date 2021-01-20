<?php
namespace Germania\DownloadsApi;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class DownloadsApiAbstract implements DownloadsApiInterface
{


	use LoggerAwareTrait,
        LoglevelTrait,
        AuthenticationTrait;


    public function __construct()
    {
        $this->setLogger( new NullLogger );
    }

    /**
     * @return iterable
     *
     * @throws \Germania\DownloadsApi\DownloadsApiExceptionInterface.
     */
    abstract public function request( string $path, array $filters = array()  ) : iterable;


	/**
     * @inheritDoc
     *
	 * @param array $filters Optional: Filter parameters
	 */
	public function all( array $filters = array() ) : iterable
	{
		return $this->request("all", $filters );
	}


	/**
     * @inheritDoc
     *
	 * @param  array $filters Optional: Filter parameters
	 */
	public function latest( array $filters = array() ) : iterable
	{
		return $this->request("latest", $filters );
	}





}
