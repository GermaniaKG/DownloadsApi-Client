<?php
namespace Germania\DownloadsApiClient;

use Psr\Log\LogLevel;

trait LoglevelTrait
{

    /**
     * PSR-3 error_loglevel name on failure
     *
     * @var string
     */
    public $error_loglevel = LogLevel::ERROR;

    /**
     * PSR-3 error_loglevel name on success
     *
     * @var string
     */
    public $success_loglevel = LogLevel::INFO;


    /**
     * @param string $loglevel \Psr\Log\LogLevel constant
     */
    public function setErrorLoglevel( string $loglevel ) : self
    {
        $this->error_loglevel = $loglevel;
        return $this;
    }


    /**
     * @param string $loglevel \Psr\Log\LogLevel constant
     */
    public function setSuccessLoglevel( string $loglevel ) : self
    {
        $this->success_loglevel = $loglevel;
        return $this;
    }

}
