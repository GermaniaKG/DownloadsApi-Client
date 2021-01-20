<?php
namespace tests;

trait LoggerTrait
{


    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;


    /**
     * @var int
     */
    public $loglevel = \Laminas\Log\Logger::DEBUG;


    protected function getLogger()
    {
        if ($this->logger) {
            return $this->logger;
        }
        $this->logger = $this->createLaminasLogger();
        return $this->logger;
    }

    protected function createLaminasLogger()
    {
        $filter = new \Laminas\Log\Filter\Priority( $this->loglevel );

        $writer = new \Laminas\Log\Writer\Stream('php://output');
        $writer->addFilter($filter);

        $laminasLogLogger = new \Laminas\Log\Logger;
        $laminasLogLogger->addWriter($writer);

        return new \Laminas\Log\PsrLoggerAdapter($laminasLogLogger);
    }
}

