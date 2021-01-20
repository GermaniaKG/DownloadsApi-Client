<?php
namespace Germania\DownloadsApiClient;

use Psr\Log\LoggerAwareTrait;

abstract class ApiClientAbstract implements ApiClientInterface
{


	use LoggerAwareTrait,
        LoglevelTrait;



	/**
     * @inheritDoc
     *
	 * @param  array  $filters
	 */
	public function all( array $filters = array() ) : iterable
	{
		return $this->__invoke("all", $filters );
	}


	/**
     * @inheritDoc
     *
	 * @param  array $filters
	 */
	public function latest( array $filters = array() ) : iterable
	{
		return $this->__invoke("latest", $filters );
	}





}
