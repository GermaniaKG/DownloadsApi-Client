<?php
namespace Germania\DownloadsApi;

use Psr\Log\LoggerAwareTrait;

abstract class DownloadsApiAbstract implements DownloadsApiInterface
{


	use LoggerAwareTrait,
        LoglevelTrait;



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
