<?php
namespace tests;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use Laminas\Log\PsrLoggerAdapter;


trait LoggerTrait
{
    use LoggerAwareTrait;

    /**
     * @var int
     */
    public $loglevel = \Laminas\Log\Logger::ALERT;


    /**
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger() : LoggerInterface
    {
        if ($this->logger) {
            return $this->logger;
        }

        $this->setLogger($this->createLaminasLogger());
        return $this->logger;
    }

    /**
     * @return \Laminas\Log\PsrLoggerAdapter
     */
    protected function createLaminasLogger() : PsrLoggerAdapter
    {
        $loglevel = ($GLOBALS['LAMINAS_LOGLEVEL'] ?? $this->loglevel) ?: $this->loglevel;
        $filter = new \Laminas\Log\Filter\Priority( $loglevel );

        $writer = new \Laminas\Log\Writer\Stream('php://output');
        $writer->addFilter($filter);

        $laminasLogLogger = new \Laminas\Log\Logger;
        $laminasLogLogger->addWriter($writer);

        return new \Laminas\Log\PsrLoggerAdapter($laminasLogLogger);
    }
}
